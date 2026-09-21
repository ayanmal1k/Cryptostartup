<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Model\CryptoTransaction;
use Carbon\Carbon;

class TransactionLimits
{
    /**
     * Handle an incoming request to check transaction limits for AML compliance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        // Coin buy/sell forms post a dollar amount (amount_usd); older forms post units x price.
        if (($request->input('sell_all') === '1' || $request->input('send_all') === '1') && $request->route('id')) {
            // "Sell all" / "Send all" post no amount: worth the whole holding at today's price.
            $wallet = \App\Models\CryptoWallet::with('cryptocurrency')
                ->where('user_id', $user->id)->where('cryptocurrency_id', $request->route('id'))->first();
            $totalValue = $wallet && $wallet->cryptocurrency
                ? (float) $wallet->balance * (float) $wallet->cryptocurrency->current_price
                : 0;
        } elseif ($request->filled('amount_usd')) {
            $totalValue = (float) $request->input('amount_usd');
        } else {
            $totalValue = (float) $request->input('amount', 0) * (float) $request->input('price_per_token', 0);
        }

        // Get user's KYC level and limits
        $kycLevel = $user->kyc_level ?? 0;
        $limits = $this->getLimits($user, $kycLevel);

        // Check per-transaction limit
        if ($totalValue > $limits['per_transaction']) {
            return $this->denyTransaction($request, 
                "Transaction exceeds your per-transaction limit of " . '$' . number_format((float) $limits['per_transaction'], 2) . ". " .
                "Please upgrade your verification level or split into smaller transactions."
            );
        }

        // Check daily limit
        $dailyTotal = $this->getDailyTransactionTotal($user->id);
        if (($dailyTotal + $totalValue) > $limits['daily']) {
            $remaining = max(0, $limits['daily'] - $dailyTotal);
            return $this->denyTransaction($request,
                "This transaction would exceed your daily limit of " . '$' . number_format((float) $limits['daily'], 2) . ". " .
                "You have " . '$' . number_format((float) $remaining, 2) . " remaining today."
            );
        }

        // Check monthly limit
        $monthlyTotal = $this->getMonthlyTransactionTotal($user->id);
        if (($monthlyTotal + $totalValue) > $limits['monthly']) {
            $remaining = max(0, $limits['monthly'] - $monthlyTotal);
            return $this->denyTransaction($request,
                "This transaction would exceed your monthly limit of " . '$' . number_format((float) $limits['monthly'], 2) . ". " .
                "You have " . '$' . number_format((float) $remaining, 2) . " remaining this month."
            );
        }

        // Check for suspicious patterns (AML)
        $suspiciousCheck = $this->checkSuspiciousActivity($user, $totalValue);
        if ($suspiciousCheck['flagged']) {
            // Log for compliance review
            \Log::channel('aml')->warning('Suspicious transaction pattern detected', [
                'user_id' => $user->id,
                'amount' => $totalValue,
                'reason' => $suspiciousCheck['reason'],
                'ip' => $request->ip(),
            ]);

            // Update user's fraud score
            $user->fraud_score = min(100, ($user->fraud_score ?? 0) + 10);
            $user->save();

            // If fraud score too high, flag the account
            if ($user->fraud_score >= 50) {
                $user->is_flagged = true;
                $user->flag_reason = $suspiciousCheck['reason'];
                $user->save();

                return $this->denyTransaction($request,
                    "Your account has been flagged for review. Please contact support."
                );
            }
        }

        // Attach limits to request for downstream use
        $request->merge([
            'daily_remaining' => $limits['daily'] - $dailyTotal,
            'monthly_remaining' => $limits['monthly'] - $monthlyTotal,
        ]);

        return $next($request);
    }

    /**
     * Get transaction limits for user based on KYC level
     */
    protected function getLimits($user, $kycLevel)
    {
        // Default limits by KYC level
        $defaultLimits = [
            0 => ['daily' => 100, 'monthly' => 500, 'per_transaction' => 50],
            1 => ['daily' => 500, 'monthly' => 2000, 'per_transaction' => 250],
            2 => ['daily' => 5000, 'monthly' => 25000, 'per_transaction' => 2500],
            3 => ['daily' => 50000, 'monthly' => 200000, 'per_transaction' => 25000],
        ];

        $limits = $defaultLimits[$kycLevel] ?? $defaultLimits[0];

        // Check if user has custom limits
        if ($user->daily_transaction_limit) {
            $limits['daily'] = min($limits['daily'], $user->daily_transaction_limit);
        }
        if ($user->monthly_transaction_limit) {
            $limits['monthly'] = min($limits['monthly'], $user->monthly_transaction_limit);
        }
        if ($user->withdrawal_limit) {
            $limits['per_transaction'] = min($limits['per_transaction'], $user->withdrawal_limit);
        }

        return $limits;
    }

    /**
     * Get user's total transaction value for today
     */
    protected function getDailyTransactionTotal($userId)
    {
        try {
            return CryptoTransaction::where(function($q) use ($userId) {
                    $q->where('buyer_user_id', $userId)
                      ->orWhere('seller_user_id', $userId);
                })
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'completed')
                ->sum('total_price') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get user's total transaction value for this month
     */
    protected function getMonthlyTransactionTotal($userId)
    {
        try {
            return CryptoTransaction::where(function($q) use ($userId) {
                    $q->where('buyer_user_id', $userId)
                      ->orWhere('seller_user_id', $userId);
                })
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->where('status', 'completed')
                ->sum('total_price') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check for suspicious activity patterns (AML compliance)
     */
    protected function checkSuspiciousActivity($user, $currentAmount)
    {
        $flagged = false;
        $reasons = [];

        // Pattern 1: Structuring (multiple transactions just under limits)
        $recentTransactions = $this->getRecentTransactions($user->id, 24);
        $justUnderLimit = 0;
        $threshold = ($user->daily_transaction_limit ?? 500) * 0.8;
        
        foreach ($recentTransactions as $tx) {
            if ($tx->total_price >= $threshold && $tx->total_price < ($threshold * 1.1)) {
                $justUnderLimit++;
            }
        }
        
        if ($justUnderLimit >= 3) {
            $flagged = true;
            $reasons[] = 'Potential structuring detected (multiple transactions near limit)';
        }

        // Pattern 2: Rapid-fire transactions
        $lastHourCount = $this->getRecentTransactions($user->id, 1)->count();
        if ($lastHourCount > 10) {
            $flagged = true;
            $reasons[] = 'Unusual transaction frequency';
        }

        // Pattern 3: New account with large transactions
        $accountAge = Carbon::parse($user->created_at)->diffInDays(now());
        if ($accountAge < 7 && $currentAmount > 1000) {
            $flagged = true;
            $reasons[] = 'Large transaction from new account';
        }

        // Pattern 4: Round number transactions (common in money laundering)
        $roundNumberCount = 0;
        foreach ($recentTransactions as $tx) {
            if ($tx->total_price == round($tx->total_price, -2)) { // Exact hundreds
                $roundNumberCount++;
            }
        }
        if ($roundNumberCount >= 5) {
            $flagged = true;
            $reasons[] = 'Multiple round-number transactions';
        }

        return [
            'flagged' => $flagged,
            'reason' => implode('; ', $reasons),
        ];
    }

    /**
     * Get recent transactions for a user
     */
    protected function getRecentTransactions($userId, $hours)
    {
        try {
            return CryptoTransaction::where(function($q) use ($userId) {
                    $q->where('buyer_user_id', $userId)
                      ->orWhere('seller_user_id', $userId);
                })
                ->where('created_at', '>=', Carbon::now()->subHours($hours))
                ->where('status', 'completed')
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Deny transaction with appropriate response
     */
    protected function denyTransaction(Request $request, $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'transaction_limit_exceeded',
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
