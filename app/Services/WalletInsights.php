<?php

namespace App\Services;

use App\Models\Cryptocurrency;
use App\Models\CryptoTransaction;
use App\Models\XpEvent;
use App\Support\Sparkline;
use App\Support\TokenMoney;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything the wallet and coin pages show beyond raw balances: membership tiers,
 * level progress, the XP week, the fastest XP still on offer today, and the value of
 * a member's coins over time. All of it is computed from real rows -- nothing here is
 * seeded, sampled or invented.
 */
final class WalletInsights
{
    /**
     * Membership tiers by level. Names and colours are presentation only; nothing in the
     * app is gated on them.
     */
    public const TIERS = [
        ['min' => 100, 'key' => 'obsidian', 'name' => 'Obsidian'],
        ['min' => 50,  'key' => 'platinum', 'name' => 'Platinum'],
        ['min' => 25,  'key' => 'gold',     'name' => 'Gold'],
        ['min' => 10,  'key' => 'silver',   'name' => 'Silver'],
        ['min' => 1,   'key' => 'bronze',   'name' => 'Bronze'],
    ];

    /** @return array{key:string,name:string,min:int,next:?array} */
    public static function tier(int $level): array
    {
        $tiers = self::TIERS;
        foreach ($tiers as $i => $t) {
            if ($level >= $t['min']) {
                $next = $tiers[$i - 1] ?? null;

                return $t + ['next' => $next];
            }
        }
        $last = end($tiers);

        return $last + ['next' => $tiers[count($tiers) - 2] ?? null];
    }

    /**
     * The level card: tier, progress, streak, today's XP, the last seven days of XP, and
     * the highest-value XP actions the member hasn't used up today.
     */
    public static function level($user): array
    {
        $level = max(1, (int) ($user->level ?? 1));
        $progress = EngagementService::levelProgress($user);
        $today = EngagementService::todaySummary($user);
        $tier = self::tier($level);

        // XP per day for the last 7 days, oldest first.
        $week = [];
        $todayCounts = collect();
        try {
            $since = Carbon::today()->subDays(6);
            $rows = XpEvent::where('user_id', $user->id)
                ->where('created_at', '>=', $since)
                ->selectRaw('DATE(created_at) as d, COALESCE(SUM(xp),0) as xp')
                ->groupBy('d')
                ->pluck('xp', 'd');
            for ($i = 0; $i < 7; $i++) {
                $day = $since->copy()->addDays($i);
                $week[] = ['label' => $day->isToday() ? __('Today') : $day->format('D'), 'xp' => (int) ($rows[$day->toDateString()] ?? 0)];
            }

            $todayCounts = XpEvent::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::today())
                ->selectRaw('action, COUNT(*) as n')
                ->groupBy('action')
                ->pluck('n', 'action');
        } catch (\Throwable $e) {
            $week = array_fill(0, 7, ['label' => '', 'xp' => 0]);
        }

        // Fastest XP still available today: biggest rewards first, skipping anything
        // capped out or automatic (visits, quests, being tipped).
        $skip = ['daily_visit', 'quest_complete', 'quest_sweep', 'tip_receive', 'sale_receive', 'profile_visit', 'video_watch', 'reaction_give'];
        $boosts = collect(EngagementService::ACTIONS)
            ->reject(fn ($def, $key) => in_array($key, $skip, true) || (int) $def['xp'] <= 0 || (int) $def['cap'] <= 0)
            ->map(fn ($def, $key) => [
                'key' => $key,
                'label' => __($def['label']),
                'xp' => (int) $def['xp'],
                'left' => max(0, (int) $def['cap'] - (int) ($todayCounts[$key] ?? 0)),
            ])
            ->filter(fn ($b) => $b['left'] > 0)
            ->sortByDesc('xp')
            ->take(3)
            ->values()
            ->all();

        return [
            'level' => $level,
            'tier' => $tier,
            'xp' => (int) ($user->xp ?? 0),
            'into' => $progress['into'],
            'span' => $progress['span'],
            'percent' => $progress['percent'],
            'to_next' => $progress['span'] - $progress['into'],
            'next_level' => $progress['next_level'],
            'levels_to_tier' => $tier['next'] ? max(0, $tier['next']['min'] - $level) : 0,
            'streak' => (int) ($user->streak_count ?? 0),
            'longest' => (int) ($user->longest_streak ?? 0),
            'sparks' => (int) ($user->sparks ?? 0),
            'today_xp' => $today['xp'],
            'week' => $week,
            'week_xp' => array_sum(array_column($week, 'xp')),
            'boosts' => $boosts,
        ];
    }

    /**
     * Value of the member's coins over time: units held after every one of their trades,
     * priced at that moment from the coin's recorded history. It is anchored on today's
     * real balances and walked backwards, so the last point always equals what the wallet
     * shows even if older ledger rows are missing.
     *
     * @param  Collection  $tokenWallets  CryptoWallet rows with `cryptocurrency` loaded
     * @return array<int,array{t:int,v:float}>  oldest first
     */
    public static function portfolioSeries(int $userId, Collection $tokenWallets): array
    {
        if ($tokenWallets->isEmpty()) {
            return [];
        }

        $coins = $tokenWallets->keyBy('cryptocurrency_id')->map->cryptocurrency;
        $units = $tokenWallets->mapWithKeys(fn ($w) => [$w->cryptocurrency_id => (float) $w->balance])->all();

        $txns = CryptoTransaction::whereIn('cryptocurrency_id', $coins->keys())
            ->whereIn('type', [CryptoTransaction::TYPE_BUY, CryptoTransaction::TYPE_SELL, CryptoTransaction::TYPE_TRANSFER])
            ->where(fn ($q) => $q->where('buyer_user_id', $userId)->orWhere('seller_user_id', $userId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get(['cryptocurrency_id', 'buyer_user_id', 'seller_user_id', 'amount', 'created_at']);

        $histories = $coins->map(fn ($c) => $c->priceHistoryPoints())->all();
        $value = function (array $units, int $t) use ($coins, $histories) {
            $sum = 0.0;
            foreach ($units as $id => $u) {
                if ($u > 0 && isset($coins[$id])) {
                    $sum += $u * self::priceAt($histories[$id] ?? [], $t, (float) $coins[$id]->current_price);
                }
            }

            return round($sum, 2);
        };

        $now = time();
        $points = [['t' => $now, 'v' => $value($units, $now)]];

        foreach ($txns as $tx) {
            $t = optional($tx->created_at)->timestamp ?? $now;
            // Value just after this trade, then undo it to get the holding before.
            $points[] = ['t' => $t, 'v' => $value($units, $t)];
            $delta = (float) $tx->amount * ((int) $tx->buyer_user_id === $userId ? 1 : -1);
            $units[$tx->cryptocurrency_id] = max(0, ($units[$tx->cryptocurrency_id] ?? 0) - $delta);
            $points[] = ['t' => $t - 1, 'v' => $value($units, $t - 1)];
        }

        $points = array_reverse($points);

        // Lead in before the first trade so the line doesn't start on a cliff at the
        // chart's edge; a lone point gets a week of history to draw a line through.
        $span = max(86400, (int) (($now - $points[0]['t']) * 0.06));
        array_unshift($points, ['t' => $points[0]['t'] - (count($points) < 3 ? 7 * 86400 : $span), 'v' => $points[0]['v']]);

        return $points;
    }

    /** A coin's price series for its chart: [{t, v}], oldest first, capped to recent points. */
    public static function priceSeries(Cryptocurrency $coin, int $take = 240): array
    {
        $points = array_slice($coin->priceHistoryPoints(), -$take);
        $series = array_map(fn ($p) => ['t' => (int) $p['timestamp'], 'v' => (float) $p['price']], $points);
        $series[] = ['t' => time(), 'v' => (float) $coin->current_price];

        return $series;
    }

    /** Step-function lookup: the last recorded price at or before $t. */
    private static function priceAt(array $history, int $t, float $fallback): float
    {
        $price = null;
        foreach ($history as $p) {
            if ($p['timestamp'] > $t) {
                break;
            }
            $price = $p['price'];
        }

        return (float) ($price ?? ($history[0]['price'] ?? $fallback));
    }

    /**
     * Holdings as the wallet shows them, biggest first. Also used by the live endpoint.
     */
    public static function holdings(Collection $tokenWallets): Collection
    {
        return $tokenWallets->map(function ($w) {
            $crypto = $w->cryptocurrency;
            $change = (float) ($crypto->price_change_24h ?? 0);
            $value = TokenMoney::toUsd((float) $w->balance, (float) $crypto->current_price);
            $prices = Sparkline::prices($crypto);

            return (object) [
                'id' => $crypto->id,
                'crypto' => $crypto,
                'value' => $value,
                'change' => $change,
                'move' => TokenMoney::changeUsd($value, $change),
                'dir' => abs($change) < 0.005 ? 'flat' : ($change > 0 ? 'up' : 'down'),
                'spark' => Sparkline::line($prices, 72, 28),
            ];
        })->sortByDesc('value')->values();
    }
}
