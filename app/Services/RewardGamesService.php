<?php

namespace App\Services;

use App\Model\User;
use App\Models\XpEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The free-to-play side of the rewards hub: a daily spin and two short skill games.
 *
 * None of this can be bought. A spin or a play costs nothing and pays out only XP,
 * sparks, streak freezes or a bronze drop — nothing redeemable for money — so it stays
 * a loyalty mechanic, not a wager.
 *
 * Integrity: every result is decided or checked on the server. The spin is rolled here
 * and the wheel only animates to it. Games are started here (a play row with a one-time
 * token and a start time) and finished here, where the elapsed time and the reported
 * score are checked against what is physically possible before anything is paid, and
 * payouts are small and capped per day, so a faked score is not worth faking.
 */
class RewardGamesService
{
    /** Wheel segments in drawing order. `weight` is relative. */
    public const SPIN = [
        ['key' => 'xp_25',       'label' => '25 XP',        'icon' => '⚡', 'type' => 'xp',     'amount' => 25,  'weight' => 24],
        ['key' => 'sparks_10',   'label' => '10 Sparks',    'icon' => '✨', 'type' => 'sparks', 'amount' => 10,  'weight' => 22],
        ['key' => 'xp_60',       'label' => '60 XP',        'icon' => '⚡', 'type' => 'xp',     'amount' => 60,  'weight' => 14],
        ['key' => 'sparks_30',   'label' => '30 Sparks',    'icon' => '✨', 'type' => 'sparks', 'amount' => 30,  'weight' => 14],
        ['key' => 'freeze',      'label' => 'Streak Freeze', 'icon' => '🧊', 'type' => 'freeze', 'amount' => 1,   'weight' => 8],
        ['key' => 'sparks_75',   'label' => '75 Sparks',    'icon' => '💫', 'type' => 'sparks', 'amount' => 75,  'weight' => 8],
        ['key' => 'xp_150',      'label' => '150 XP',       'icon' => '🚀', 'type' => 'xp',     'amount' => 150, 'weight' => 6],
        ['key' => 'drop_bronze', 'label' => 'Bronze Drop',  'icon' => '🎁', 'type' => 'drop',   'amount' => 1,   'weight' => 4],
    ];

    /**
     * Skill games. `min_seconds` is the fastest a genuine play can finish; `max_score` is
     * the physical ceiling. Rewards scale with performance and are capped.
     */
    public const GAMES = [
        'tap' => [
            'label' => 'Tap Rush', 'icon' => '🎯', 'plays' => 3,
            'blurb' => 'Hit as many targets as you can in 20 seconds.',
            'duration' => 20, 'min_seconds' => 19, 'max_score' => 70,
        ],
        'memory' => [
            'label' => 'Memory Flip', 'icon' => '🃏', 'plays' => 3,
            'blurb' => 'Match all 8 pairs in as few moves as you can.',
            'pairs' => 8, 'min_seconds' => 6,
        ],
    ];

    /* ------------------------------------------------------------------ spin -- */

    public static function spunToday(User $user): bool
    {
        return self::playsToday($user, 'spin') > 0;
    }

    /**
     * Spin the wheel. Returns ['index' => segment index, 'reward' => segment] or null if
     * already spun today. Rolled and paid in one locked transaction.
     */
    public static function spin(User $user): ?array
    {
        return DB::transaction(function () use ($user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$locked || self::playsToday($locked, 'spin') > 0) {
                return null;
            }

            $index = self::rollIndex();
            $segment = self::SPIN[$index];

            self::pay($locked, $segment['type'], (int) $segment['amount'], 'game_spin');
            self::mirror($user, $locked);

            DB::table('reward_plays')->insert([
                'user_id' => $user->id,
                'game' => 'spin',
                'play_date' => Carbon::today()->toDateString(),
                'result_key' => $segment['key'],
                'xp' => $segment['type'] === 'xp' ? $segment['amount'] : 0,
                'sparks' => $segment['type'] === 'sparks' ? $segment['amount'] : 0,
                'started_at' => now(),
                'finished_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['index' => $index, 'reward' => $segment];
        });
    }

    protected static function rollIndex(): int
    {
        $total = array_sum(array_column(self::SPIN, 'weight'));
        $roll = random_int(1, $total);
        foreach (self::SPIN as $i => $segment) {
            $roll -= $segment['weight'];
            if ($roll <= 0) {
                return $i;
            }
        }

        return 0;
    }

    /* ----------------------------------------------------------------- games -- */

    public static function playsLeft(User $user, string $game): int
    {
        $def = self::GAMES[$game] ?? null;

        return $def ? max(0, $def['plays'] - self::playsToday($user, $game)) : 0;
    }

    /** Start a play. Returns ['play' => id, 'token' => string] or null when out of plays. */
    public static function start(User $user, string $game): ?array
    {
        if (!isset(self::GAMES[$game])) {
            return null;
        }

        return DB::transaction(function () use ($user, $game) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if (self::playsLeft($user, $game) <= 0) {
                return null;
            }

            $token = Str::random(40);
            $id = DB::table('reward_plays')->insertGetId([
                'user_id' => $user->id,
                'game' => $game,
                'play_date' => Carbon::today()->toDateString(),
                'token' => $token,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['play' => $id, 'token' => $token];
        });
    }

    /**
     * Finish a play and pay out. $result is the score (tap) or the move count (memory).
     * Returns ['xp' => int, 'sparks' => int, 'score' => int] or a string error.
     *
     * @return array|string
     */
    public static function finish(User $user, string $game, int $playId, string $token, int $result)
    {
        $def = self::GAMES[$game] ?? null;
        if (!$def) {
            return 'Unknown game.';
        }

        return DB::transaction(function () use ($user, $game, $def, $playId, $token, $result) {
            $play = DB::table('reward_plays')->where('id', $playId)->lockForUpdate()->first();
            if (!$play || (int) $play->user_id !== (int) $user->id || $play->game !== $game
                || $play->finished_at !== null || !hash_equals((string) $play->token, $token)) {
                return 'That game has already ended.';
            }

            $elapsed = Carbon::parse($play->started_at)->diffInSeconds(now());
            if ($elapsed < $def['min_seconds'] || $elapsed > 600) {
                return 'That play did not count — try again.';
            }

            [$xp, $sparks, $score] = self::rewardFor($game, $def, $result);

            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if ($xp > 0) {
                self::pay($locked, 'xp', $xp, 'game_' . $game);
            }
            if ($sparks > 0) {
                self::pay($locked, 'sparks', $sparks, null);
            }
            self::mirror($user, $locked);

            DB::table('reward_plays')->where('id', $playId)->update([
                'score' => $score,
                'xp' => $xp,
                'sparks' => $sparks,
                'token' => null,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            return ['xp' => $xp, 'sparks' => $sparks, 'score' => $score];
        });
    }

    /** @return array{0:int,1:int,2:int} xp, sparks, recorded score */
    protected static function rewardFor(string $game, array $def, int $result): array
    {
        if ($game === 'tap') {
            $score = max(0, min($def['max_score'], $result));

            return [min(40, $score), min(12, intdiv($score, 5)), $score];
        }

        // Memory: fewer moves is better. A perfect game is one move per pair.
        $moves = max($def['pairs'], $result);
        $spare = max(0, 24 - $moves);          // 24+ moves earns the floor

        return [20 + min(20, $spare), 4 + min(8, intdiv($spare, 2)), $moves];
    }

    /* --------------------------------------------------------------- helpers -- */

    protected static function playsToday(User $user, string $game): int
    {
        try {
            return DB::table('reward_plays')
                ->where('user_id', $user->id)
                ->where('game', $game)
                ->whereDate('play_date', Carbon::today()->toDateString())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Apply a payout to a locked user row (saved here) and audit XP/sparks. */
    protected static function pay(User $locked, string $type, int $amount, ?string $auditAction): void
    {
        switch ($type) {
            case 'xp':
                $before = (int) ($locked->level ?? 1);
                $locked->xp = (int) ($locked->xp ?? 0) + $amount;
                $locked->level = intdiv((int) $locked->xp, GamificationService::XP_PER_LEVEL) + 1;
                if ((int) $locked->level > $before) {
                    GamificationService::pushEvent(['type' => 'level', 'level' => (int) $locked->level]);
                }
                break;
            case 'sparks':
                $locked->sparks = (int) ($locked->sparks ?? 0) + $amount;
                $locked->lifetime_sparks = (int) ($locked->lifetime_sparks ?? 0) + $amount;
                break;
            case 'freeze':
                $locked->streak_freezes = (int) ($locked->streak_freezes ?? 0) + $amount;
                break;
            case 'drop':
                DropService::grant($locked, 'bronze', 'daily_spin');
                break;
        }
        $locked->save();

        if (in_array($type, ['xp', 'sparks'], true)) {
            try {
                XpEvent::create([
                    'user_id' => $locked->id,
                    'action' => $auditAction ?: 'game_reward',
                    'xp' => $type === 'xp' ? $amount : 0,
                    'sparks' => $type === 'sparks' ? $amount : 0,
                ]);
            } catch (\Throwable $e) {
                // the audit row is not worth failing a payout over
            }
        }
    }

    protected static function mirror(User $user, User $locked): void
    {
        foreach (['xp', 'level', 'sparks', 'lifetime_sparks', 'streak_freezes'] as $field) {
            $user->{$field} = $locked->{$field};
        }
    }
}
