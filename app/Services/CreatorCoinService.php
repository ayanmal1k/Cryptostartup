<?php

namespace App\Services;

use App\Model\User;
use App\Model\Wallet;
use App\Models\CreatorCoin;
use App\Models\CreatorCoinBalance;
use App\Models\CreatorCoinTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Business logic for Creator Coins (non-cashable loyalty points).
 *
 * Money safety: every balance-changing operation runs inside a DB transaction and locks the
 * affected wallet/balance rows (lockForUpdate) so concurrent requests can't double-spend.
 * There is intentionally NO points -> credits path: fans can only spend points on perks.
 */
class CreatorCoinService
{
    public function createCoin(User $creator, array $data): CreatorCoin
    {
        if (CreatorCoin::where('creator_user_id', $creator->id)->exists()) {
            throw new RuntimeException('You already have a creator coin.');
        }

        return CreatorCoin::create([
            'creator_user_id' => $creator->id,
            'name' => $data['name'],
            'symbol' => strtoupper($data['symbol']),
            'logo' => $data['logo'] ?? null,
            'description' => $data['description'] ?? null,
            'price_per_point' => $data['price_per_point'],
            'platform_fee_percentage' => $data['platform_fee_percentage']
                ?? config('creator_coins.platform_fee_percentage', 10),
            'is_active' => true,
        ]);
    }

    /**
     * Give a user their coin automatically, deriving name/symbol from their profile.
     *
     * Idempotent: returns the existing coin if they already have one. Deliberately
     * BEST-EFFORT — any failure is swallowed and logged, because a coin is never worth
     * failing a user's registration over. Callers get null on failure.
     */
    public function provisionFor(User $creator): ?CreatorCoin
    {
        if (!config('creator_coins.auto_provision', true)) {
            return null;
        }

        try {
            $existing = CreatorCoin::where('creator_user_id', $creator->id)->first();
            if ($existing) {
                return $existing;
            }

            $display = trim((string) ($creator->name ?: $creator->username)) ?: 'Creator';

            return CreatorCoin::create([
                'creator_user_id' => $creator->id,
                'name' => Str::limit($display, 60, '') . ' Coin',
                'symbol' => $this->deriveSymbol($creator),
                'description' => null,
                'price_per_point' => config('creator_coins.default_price_per_point', 0.10),
                'platform_fee_percentage' => config('creator_coins.platform_fee_percentage', 10),
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Build a ticker from the username: alphanumerics only, uppercased, <= 8 chars so it stays
     * readable. Falls back to an id-based symbol when the username yields nothing usable.
     * The column allows 16 chars, so the id suffix can never overflow it.
     */
    private function deriveSymbol(User $creator): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $creator->username));
        $base = substr($base, 0, 8);

        if ($base === '' || ctype_digit($base)) {
            return 'C' . $creator->id;
        }

        // Disambiguate collisions rather than shipping two identical tickers.
        if (CreatorCoin::where('symbol', $base)->exists()) {
            return substr($base, 0, 8) . $creator->id;
        }

        return $base;
    }

    /**
     * Fan buys $points of $coin using platform credits. Debits the fan's credit wallet, credits
     * the creator's wallet (minus platform fee), issues points, and records the ledger row.
     */
    public function purchase(CreatorCoin $coin, User $fan, float $points): CreatorCoinTransaction
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Amount of points must be greater than zero.');
        }
        if ((int) $coin->creator_user_id === (int) $fan->id) {
            throw new RuntimeException('You cannot buy your own coin.');
        }
        if (!$coin->is_active) {
            throw new RuntimeException('This coin is not currently available.');
        }

        $cost = round($points * (float) $coin->price_per_point, 2);
        $platformFee = round($cost * (float) $coin->platform_fee_percentage / 100, 2);
        $creatorEarnings = round($cost - $platformFee, 2);

        return DB::transaction(function () use ($coin, $fan, $points, $cost, $platformFee, $creatorEarnings) {
            $fanWallet = $this->lockedWallet((int) $fan->id);
            if ((float) $fanWallet->total < $cost) {
                throw new RuntimeException('Insufficient platform credits. This purchase costs ' . $cost . ' credits.');
            }
            $creatorWallet = $this->lockedWallet((int) $coin->creator_user_id);

            // Move platform credits (fan pays; creator earns withdrawable credits minus fee).
            $fanWallet->update(['total' => (float) $fanWallet->total - $cost]);
            $creatorWallet->update(['total' => (float) $creatorWallet->total + $creatorEarnings]);

            // Issue points to the fan.
            $balance = CreatorCoinBalance::where('creator_coin_id', $coin->id)
                ->where('user_id', $fan->id)
                ->lockForUpdate()
                ->first();
            if (!$balance) {
                $balance = CreatorCoinBalance::create([
                    'creator_coin_id' => $coin->id,
                    'user_id' => $fan->id,
                    'balance' => 0,
                ]);
            }
            $newBalance = (float) $balance->balance + $points;
            $balance->update(['balance' => $newBalance]);

            $coin->increment('points_issued', $points);

            return CreatorCoinTransaction::create([
                'creator_coin_id' => $coin->id,
                'user_id' => $fan->id,
                'type' => CreatorCoinTransaction::TYPE_PURCHASE,
                'points' => $points,
                'balance_after' => $newBalance,
                'credits_amount' => $cost,
                'platform_fee' => $platformFee,
                'counterparty_user_id' => $coin->creator_user_id,
                'notes' => 'Purchased ' . rtrim(rtrim(number_format($points, 8, '.', ''), '0'), '.')
                    . ' ' . $coin->symbol . ' for ' . $cost . ' credits',
            ]);
        });
    }

    /**
     * Fan spends points on a perk. Returns the ledger row. (Perk wiring comes later; this is the
     * primitive that unlock/tip features will call.)
     */
    public function spend(
        CreatorCoin $coin,
        User $fan,
        float $points,
        ?string $referenceType = null,
        $referenceId = null,
        ?string $notes = null
    ): CreatorCoinTransaction {
        if ($points <= 0) {
            throw new InvalidArgumentException('Amount of points must be greater than zero.');
        }

        return DB::transaction(function () use ($coin, $fan, $points, $referenceType, $referenceId, $notes) {
            $balance = CreatorCoinBalance::where('creator_coin_id', $coin->id)
                ->where('user_id', $fan->id)
                ->lockForUpdate()
                ->first();

            if (!$balance || (float) $balance->balance < $points) {
                throw new RuntimeException('Insufficient points.');
            }

            $newBalance = (float) $balance->balance - $points;
            $balance->update(['balance' => $newBalance]);

            return CreatorCoinTransaction::create([
                'creator_coin_id' => $coin->id,
                'user_id' => $fan->id,
                'type' => CreatorCoinTransaction::TYPE_SPEND,
                'points' => $points,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Fetch a user's credit wallet locked for update, creating it if missing.
     */
    private function lockedWallet(int $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            Wallet::create(['id' => (string) Str::uuid(), 'user_id' => $userId, 'total' => 0]);
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        }

        return $wallet;
    }
}
