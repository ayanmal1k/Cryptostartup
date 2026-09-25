<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// Use correct model namespace
use App\Model\Cryptocurrency;
use App\Model\CryptoWallet;
use App\Model\CryptoTransaction;
use Carbon\Carbon;

class CryptoOnRampController extends Controller
{
    /**
     * Supported fiat currencies and their exchange rates to USD
     */
    protected $supportedCurrencies = [
        'USD' => 1.00,
        'EUR' => 1.08,
        'GBP' => 1.27,
        'CAD' => 0.74,
        'AUD' => 0.65,
    ];

    /**
     * Platform token (site's native token)
     */
    protected $platformToken = null;

    public function __construct()
    {
        $this->middleware(['auth', 'verified', '2fa']);
    }

    /**
     * Show the deposit/buy crypto page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get platform token
        $platformToken = $this->getPlatformToken();
        
        // Get user's current wallet balance
        $wallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->get();
        
        // Get recent transactions
        $recentTransactions = CryptoTransaction::where(function($q) use ($user) {
                $q->where('buyer_user_id', $user->id)
                  ->orWhere('seller_user_id', $user->id);
            })
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get transaction limits based on KYC level
        $limits = $this->getTransactionLimits($user);
        
        return view('cryptocurrency.onramp.index', compact(
            'user',
            'platformToken',
            'wallets',
            'recentTransactions',
            'limits'
        ));
    }

    /**
     * Show the buy tokens form
     */
    public function buyForm(Request $request)
    {
        $user = Auth::user();
        $platformToken = $this->getPlatformToken();
        
        if (!$platformToken) {
            return redirect()->route('cryptocurrency.wallet')
                ->with('error', 'Platform token not configured. Please contact support.');
        }
        
        $limits = $this->getTransactionLimits($user);
        $paymentMethods = $this->getAvailablePaymentMethods($user);
        
        return view('cryptocurrency.onramp.buy', compact(
            'user',
            'platformToken',
            'limits',
            'paymentMethods'
        ));
    }

    /**
     * Process crypto payment purchase of tokens
     */
    public function processPurchase(Request $request)
    {
        $user = Auth::user();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'amount_usd' => 'required|numeric|min:5|max:50000',
            'payment_crypto' => 'required|in:bitcoin,ethereum,usdt,usdc',
            'crypto_amount' => 'required|numeric|min:0.00000001',
            'transaction_hash' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $amountUsd = (float) $request->input('amount_usd');
        $paymentCrypto = $request->input('payment_crypto');
        $cryptoAmount = (float) $request->input('crypto_amount');
        $transactionHash = $request->input('transaction_hash');
        
        // Check transaction limits
        $limits = $this->getTransactionLimits($user);
        $dailyTotal = $this->getDailyDepositTotal($user->id);
        
        if ($amountUsd > $limits['per_transaction']) {
            return redirect()->back()
                ->with('error', "Transaction exceeds your per-transaction limit of \${$limits['per_transaction']}.")
                ->withInput();
        }
        
        if (($dailyTotal + $amountUsd) > $limits['daily']) {
            $remaining = max(0, $limits['daily'] - $dailyTotal);
            return redirect()->back()
                ->with('error', "This would exceed your daily deposit limit. You have \${$remaining} remaining today.")
                ->withInput();
        }

        // Get platform token
        $platformToken = $this->getPlatformToken();
        if (!$platformToken) {
            return redirect()->back()
                ->with('error', 'Platform token not available. Please try again later.')
                ->withInput();
        }

        try {
            \DB::beginTransaction();
            
            // Get or create payment crypto wallet for user
            $paymentCryptoId = $this->getPaymentCryptoId($paymentCrypto);
            if (!$paymentCryptoId) {
                \DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Selected cryptocurrency not supported.')
                    ->withInput();
            }
            
            // Add crypto to user's wallet (the crypto they paid with)
            $paymentWallet = CryptoWallet::firstOrNew([
                'user_id' => $user->id,
                'cryptocurrency_id' => $paymentCryptoId,
            ]);
            $paymentWallet->balance = ($paymentWallet->balance ?? 0) + $cryptoAmount;
            $paymentWallet->save();
            
            // Calculate fees (minimal for crypto payments)
            $platformFeePercent = 1.0; // 1% platform fee only
            $feeAmount = $amountUsd * ($platformFeePercent / 100);
            $netAmount = $amountUsd - $feeAmount;
            
            // Calculate tokens to receive
            $tokenPrice = $platformToken->current_price;
            $tokensToReceive = $netAmount / $tokenPrice;
            
            // Update or create user's platform token wallet
            $tokenWallet = CryptoWallet::firstOrNew([
                'user_id' => $user->id,
                'cryptocurrency_id' => $platformToken->id,
            ]);
            $tokenWallet->balance = ($tokenWallet->balance ?? 0) + $tokensToReceive;
            $tokenWallet->save();
            
            // Update token supply
            $platformToken->available_supply -= $tokensToReceive;
            $platformToken->circulating_supply += $tokensToReceive;
            $platformToken->volume_24h += $amountUsd;
            $platformToken->save();
            
            // Create transaction record for crypto deposit
            $cryptoTransaction = CryptoTransaction::create([
                'cryptocurrency_id' => $paymentCryptoId,
                'buyer_user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $cryptoAmount,
                'price_per_token' => $this->getCryptoPrice($paymentCrypto),
                'total_price' => $amountUsd,
                'fee_amount' => 0,
                'status' => 'completed',
                'transaction_hash' => $transactionHash ?: 'crypto_' . time() . '_' . substr(md5(uniqid()), 0, 16),
                'notes' => json_encode([
                    'payment_method' => $paymentCrypto,
                    'crypto_amount' => $cryptoAmount,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
            ]);
            
            // Create transaction record for token distribution
            $tokenTransaction = CryptoTransaction::create([
                'cryptocurrency_id' => $platformToken->id,
                'buyer_user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $tokensToReceive,
                'price_per_token' => $tokenPrice,
                'total_price' => $amountUsd,
                'fee_amount' => $feeAmount,
                'status' => 'completed',
                'transaction_hash' => 'token_dist_' . time() . '_' . substr(md5(uniqid()), 0, 16),
                'notes' => json_encode([
                    'source' => 'crypto_payment',
                    'source_crypto' => $paymentCrypto,
                    'source_transaction_id' => $cryptoTransaction->id,
                    'platform_fee' => $feeAmount,
                    'net_amount_usd' => $netAmount,
                    'token_price_at_purchase' => $tokenPrice,
                ]),
            ]);
            
            \DB::commit();
            
            // Log for compliance
            Log::channel('crypto')->info('Crypto payment purchase completed', [
                'transaction_id' => $tokenTransaction->id,
                'user_id' => $user->id,
                'amount_usd' => $amountUsd,
                'payment_crypto' => $paymentCrypto,
                'crypto_amount' => $cryptoAmount,
                'tokens_received' => $tokensToReceive,
                'ip' => $request->ip(),
            ]);
            
            return redirect()->route('cryptocurrency.wallet')
                // Dollars and the coin's name only, never a unit count or ticker.
                ->with('success', 'You bought ' . \App\Support\TokenMoney::format($netAmount)
                    . ' of ' . $platformToken->name . ". It's in your wallet now.");
                
        } catch (\Exception $e) {
            \DB::rollBack();
            
            Log::channel('crypto')->error('Crypto payment purchase failed', [
                'user_id' => $user->id,
                'amount_usd' => $amountUsd,
                'payment_crypto' => $paymentCrypto,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while processing your purchase. Please try again.')
                ->withInput();
        }
    }

    /**
     * Get payment cryptocurrency ID by symbol
     */
    protected function getPaymentCryptoId($cryptoSymbol)
    {
        $cryptoMap = [
            'bitcoin' => 'BTC',
            'ethereum' => 'ETH',
            'usdt' => 'USDT',
            'usdc' => 'USDC',
        ];
        
        $symbol = $cryptoMap[$cryptoSymbol] ?? $cryptoSymbol;
        
        $crypto = Cryptocurrency::where('symbol', $symbol)
            ->where('is_active', true)
            ->first();
        
        // If crypto doesn't exist, create it
        if (!$crypto) {
            $crypto = Cryptocurrency::create([
                'name' => ucfirst($cryptoSymbol),
                'symbol' => $symbol,
                'current_price' => $this->getCryptoPrice($cryptoSymbol),
                'is_active' => true,
                'is_verified' => true,
            ]);
        }
        
        return $crypto->id;
    }
    
    /**
     * Get current price for a cryptocurrency
     */
    protected function getCryptoPrice($cryptoSymbol)
    {
        // Mock prices - in production, fetch from API
        $prices = [
            'bitcoin' => 45000.00,
            'ethereum' => 2800.00,
            'usdt' => 1.00,
            'usdc' => 1.00,
        ];
        
        return $prices[$cryptoSymbol] ?? 1.00;
    }

    /**
     * Get the platform's native token
     */
    protected function getPlatformToken()
    {
        if ($this->platformToken) {
            return $this->platformToken;
        }
        
        // Get the platform token (usually the first verified token or one marked as platform token)
        $this->platformToken = Cryptocurrency::where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('id', 'asc')
            ->first();
        
        // Fallback: get any active token
        if (!$this->platformToken) {
            $this->platformToken = Cryptocurrency::where('is_active', true)
                ->orderBy('id', 'asc')
                ->first();
        }
        
        return $this->platformToken;
    }

    /**
     * Get transaction limits based on user's KYC level
     */
    protected function getTransactionLimits($user)
    {
        $kycLevel = $user->kyc_level ?? 0;
        
        $limits = [
            0 => ['daily' => 100, 'monthly' => 500, 'per_transaction' => 50],
            1 => ['daily' => 1000, 'monthly' => 5000, 'per_transaction' => 500],
            2 => ['daily' => 10000, 'monthly' => 50000, 'per_transaction' => 5000],
            3 => ['daily' => 100000, 'monthly' => 500000, 'per_transaction' => 50000],
        ];
        
        return $limits[$kycLevel] ?? $limits[0];
    }

    /**
     * Get daily deposit total for user
     */
    protected function getDailyDepositTotal($userId)
    {
        try {
            return CryptoTransaction::where('buyer_user_id', $userId)
                ->where('type', 'deposit')
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'completed')
                ->sum('total_price') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get available payment methods for user (crypto only)
     */
    protected function getAvailablePaymentMethods($user)
    {
        $methods = [];
        
        // Bitcoin
        $methods['bitcoin'] = [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'icon' => 'fa-brands fa-bitcoin',
            'fee' => '1.0%',
            'instant' => true,
        ];
        
        // Ethereum
        $methods['ethereum'] = [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'icon' => 'fa-brands fa-ethereum',
            'fee' => '1.0%',
            'instant' => true,
        ];
        
        // USDT
        $methods['usdt'] = [
            'name' => 'Tether',
            'symbol' => 'USDT',
            'icon' => 'fa-coins',
            'fee' => '1.0%',
            'instant' => true,
        ];
        
        // USDC
        $methods['usdc'] = [
            'name' => 'USD Coin',
            'symbol' => 'USDC',
            'icon' => 'fa-coins',
            'fee' => '1.0%',
            'instant' => true,
        ];
        
        return $methods;
    }

    /**
     * Get current exchange rate quote
     */
    public function getQuote(Request $request)
    {
        $amountUsd = (float) $request->input('amount', 0);
        $paymentCrypto = $request->input('payment_crypto', 'bitcoin');
        $platformToken = $this->getPlatformToken();
        
        if (!$platformToken || $amountUsd <= 0) {
            return response()->json(['success' => false]);
        }
        
        $cryptoPrice = $this->getCryptoPrice($paymentCrypto);
        $cryptoAmount = $amountUsd / $cryptoPrice;
        
        $platformFee = $amountUsd * 0.01; // 1% platform fee only
        $netAmount = $amountUsd - $platformFee;
        $tokensToReceive = $netAmount / $platformToken->current_price;
        
        return response()->json([
            'success' => true,
            'amount_usd' => $amountUsd,
            'payment_crypto' => $paymentCrypto,
            'crypto_price' => $cryptoPrice,
            'crypto_amount' => round($cryptoAmount, 8),
            'platform_fee' => round($platformFee, 2),
            'total_fees' => round($platformFee, 2),
            'net_amount' => round($netAmount, 2),
            'token_price' => $platformToken->current_price,
            'tokens_to_receive' => round($tokensToReceive, 8),
            'token_symbol' => $platformToken->symbol,
        ]);
    }
}
