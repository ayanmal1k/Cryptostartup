<?php

namespace App\Services;

use App\Model\User;
use App\Models\UserAchievement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight gamification: daily streaks + XP + levels.
 * No external packages — runs on standard shared hosting.
 */
class GamificationService
{
    /** XP required to advance one level. */
    const XP_PER_LEVEL = 100;

    /** Base XP awarded for a daily visit. */
    const XP_DAILY_VISIT = 10;

    /** Celebration events (level-ups / badge unlocks) collected during a request. */
    protected static $events = [];

    /** Return and clear the celebration events queued this request. */
    public static function pullEvents()
    {
        $events = self::$events;
        self::$events = [];
        return $events;
    }

    /**
     * Queue a celebration event from outside this class.
     *
     * EngagementService awards XP on its own path (it also moves sparks, which this
     * class knows nothing about), so it needs a way to put a level-up toast on the
     * same queue the celebration partial already drains.
     */
    public static function pushEvent(array $event)
    {
        self::$events[] = $event;
    }

    /**
     * Tick the user's daily streak. Safe to call on every request —
     * it only writes once per calendar day.
     */
    public static function touchDailyStreak(User $user)
    {
        $today = Carbon::today();
        $last = $user->last_activity_date ? Carbon::parse($user->last_activity_date) : null;

        // Already counted today — nothing to do.
        if ($last && $last->isSameDay($today)) {
            return;
        }

        $oldLevel = (int) ($user->level ?? 1);

        $gap = $last ? $last->diffInDays($today) : null;
        $usedFreeze = false;

        if ($gap === 1) {
            // Visited yesterday → streak continues.
            $user->streak_count = (int) ($user->streak_count ?? 0) + 1;
        } elseif ($gap === 2 && (int) ($user->streak_freezes ?? 0) > 0) {
            // Missed exactly one day, but the user is holding a freeze. Spend it and keep
            // the streak alive.
            //
            // This is the single highest-value retention mechanic here, and it is the one
            // that makes a long streak worth caring about: losing a 40-day streak to one
            // bad day is the moment people stop coming back entirely. A freeze turns that
            // into a near miss they survived. It only covers a single missed day -- longer
            // gaps still reset, or the number stops meaning anything.
            $user->streak_freezes = (int) $user->streak_freezes - 1;
            $user->streak_frozen_on = $today->toDateString();
            $user->streak_count = (int) ($user->streak_count ?? 0) + 1;
            $usedFreeze = true;
        } else {
            // First ever visit, or streak was broken → restart at 1.
            $user->streak_count = 1;
        }

        $user->last_activity_date = $today->toDateString();

        if ((int) ($user->longest_streak ?? 0) < $user->streak_count) {
            $user->longest_streak = $user->streak_count;
        }

        // Daily XP + a small bonus that grows with the streak (capped).
        $streakBonus = min((int) $user->streak_count, 7) * 2;
        self::applyXp($user, self::XP_DAILY_VISIT + $streakBonus);

        // Sparks for showing up. Guarded because this column arrives in a later migration
        // than the streak columns, and a half-migrated server must not 500 on every page.
        try {
            $dailySparks = 5 + min((int) $user->streak_count, 10);
            $user->sparks = (int) ($user->sparks ?? 0) + $dailySparks;
            $user->lifetime_sparks = (int) ($user->lifetime_sparks ?? 0) + $dailySparks;
        } catch (\Throwable $e) {
            // sparks columns not migrated yet
        }

        if ($usedFreeze) {
            self::$events[] = [
                'type' => 'freeze',
                'name' => 'Streak saved',
                'icon' => '🧊',
            ];
        }

        // Milestone streaks hand out a box. Chasing day 7 is a far stronger pull when
        // something concrete lands at the end of it.
        if (in_array((int) $user->streak_count, [3, 7, 14, 30, 60, 100], true)) {
            try {
                $tier = (int) $user->streak_count >= 30 ? 'gold'
                    : ((int) $user->streak_count >= 7 ? 'silver' : 'bronze');
                DropService::grant($user, $tier, 'streak');
            } catch (\Throwable $e) {
                // drops not migrated yet
            }
        }

        // Unlock any newly-earned achievements (safe if that table isn't migrated yet).
        try {
            self::checkAndUnlockAchievements($user);
        } catch (\Throwable $e) {
            // ignore — never block the streak save
        }

        if ((int) $user->level > $oldLevel) {
            self::$events[] = ['type' => 'level', 'level' => (int) $user->level];
        }

        $user->save();
    }

    /**
     * Achievement definitions. Icons are emoji so they render everywhere
     * without depending on an icon font.
     */
    /** Display order + labels for the achievement groups. */
    public const CATEGORIES = [
        'start'   => 'Getting Started',
        'streak'  => 'Streaks',
        'level'   => 'Levels',
        'support' => 'Supporting Creators',
        'create'  => 'Creating',
    ];

    /**
     * Achievement definitions. Icons are emoji so they render everywhere without depending
     * on an icon font.
     *
     * Each entry names a `metric` (a key from userStats()) and a `target`. Expressing them
     * this way — rather than as opaque check closures — means the UI can show real progress
     * ("4 / 7 days") toward everything still locked, which is the part that actually pulls
     * people back.
     */
    public static function achievements()
    {
        return [
            // Getting started ------------------------------------------------------------
            'welcome'      => ['name' => 'Welcome!',       'desc' => 'Joined the community.',        'icon' => '👋', 'xp' => 20,  'category' => 'start',   'metric' => 'account',      'target' => 1,   'unit' => ''],
            'first_post'   => ['name' => 'First Words',    'desc' => 'Published your first post.',   'icon' => '📝', 'xp' => 30,  'category' => 'start',   'metric' => 'posts',        'target' => 1,   'unit' => 'posts'],
            'first_video'  => ['name' => 'Lights, Camera', 'desc' => 'Uploaded your first video.',   'icon' => '🎬', 'xp' => 40,  'category' => 'start',   'metric' => 'videos',       'target' => 1,   'unit' => 'videos'],

            // Streaks --------------------------------------------------------------------
            'streak_3'     => ['name' => 'On Fire',        'desc' => 'Reached a 3-day streak.',      'icon' => '🔥', 'xp' => 30,  'category' => 'streak',  'metric' => 'streak',       'target' => 3,   'unit' => 'days'],
            'streak_7'     => ['name' => 'Week Warrior',   'desc' => 'Reached a 7-day streak.',      'icon' => '⚡', 'xp' => 70,  'category' => 'streak',  'metric' => 'streak',       'target' => 7,   'unit' => 'days'],
            'streak_14'    => ['name' => 'Committed',      'desc' => 'Reached a 14-day streak.',     'icon' => '💪', 'xp' => 140, 'category' => 'streak',  'metric' => 'streak',       'target' => 14,  'unit' => 'days'],
            'streak_30'    => ['name' => 'Unstoppable',    'desc' => 'Reached a 30-day streak.',     'icon' => '👑', 'xp' => 300, 'category' => 'streak',  'metric' => 'streak',       'target' => 30,  'unit' => 'days'],

            // Levels ---------------------------------------------------------------------
            'level_5'      => ['name' => 'Rising Star',    'desc' => 'Reached level 5.',             'icon' => '⭐', 'xp' => 50,  'category' => 'level',   'metric' => 'level',        'target' => 5,   'unit' => ''],
            'level_10'     => ['name' => 'Veteran',        'desc' => 'Reached level 10.',            'icon' => '🏆', 'xp' => 100, 'category' => 'level',   'metric' => 'level',        'target' => 10,  'unit' => ''],
            'level_25'     => ['name' => 'Legend',         'desc' => 'Reached level 25.',            'icon' => '💎', 'xp' => 250, 'category' => 'level',   'metric' => 'level',        'target' => 25,  'unit' => ''],

            // Supporting creators --------------------------------------------------------
            'first_buy'    => ['name' => 'First Support',  'desc' => 'Made your first purchase.',    'icon' => '🎁', 'xp' => 50,  'category' => 'support', 'metric' => 'purchases',    'target' => 1,   'unit' => ''],
            'supporter_5'  => ['name' => 'True Fan',       'desc' => 'Supported creators 5 times.',  'icon' => '💖', 'xp' => 120, 'category' => 'support', 'metric' => 'purchases',    'target' => 5,   'unit' => ''],
            'supporter_25' => ['name' => 'Superfan',       'desc' => 'Supported creators 25 times.', 'icon' => '🌟', 'xp' => 400, 'category' => 'support', 'metric' => 'purchases',    'target' => 25,  'unit' => ''],
            'coin_holder'  => ['name' => 'Coin Holder',    'desc' => 'Hold a creator coin.',         'icon' => '🪙', 'xp' => 80,  'category' => 'support', 'metric' => 'coins_held',   'target' => 1,   'unit' => 'coins'],
            'collector'    => ['name' => 'Collector',      'desc' => 'Own your first NFT.',          'icon' => '🖼️', 'xp' => 100, 'category' => 'support', 'metric' => 'nfts_owned',   'target' => 1,   'unit' => 'NFTs'],

            // Creating -------------------------------------------------------------------
            'first_mint'   => ['name' => 'Minted',         'desc' => 'Minted your first NFT.',       'icon' => '⛏️', 'xp' => 120, 'category' => 'create',  'metric' => 'nfts_minted',  'target' => 1,   'unit' => 'NFTs'],
            'creator_10'   => ['name' => 'Storyteller',    'desc' => 'Published 10 posts.',          'icon' => '📚', 'xp' => 150, 'category' => 'create',  'metric' => 'posts',        'target' => 10,  'unit' => 'posts'],
            'video_5'      => ['name' => 'Show Runner',    'desc' => 'Uploaded 5 videos.',           'icon' => '📹', 'xp' => 200, 'category' => 'create',  'metric' => 'videos',       'target' => 5,   'unit' => 'videos'],
        ];
    }

    /**
     * Current value of every metric the achievements measure, for one user.
     *
     * Each lookup is guarded individually: a table that hasn't been migrated yet (or a model
     * that isn't present) yields 0 for that metric instead of breaking the whole page.
     *
     * Called at most once per user per day from touchDailyStreak(), plus once per view of
     * the achievements page.
     */
    public static function userStats(User $user): array
    {
        $count = function (callable $fn) {
            try {
                return (int) $fn();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $userId = $user->id;

        return [
            'account'     => 1,
            'streak'      => (int) ($user->streak_count ?? 0),
            'level'       => (int) ($user->level ?? 1),
            'posts'       => $count(fn () => DB::table('posts')->where('user_id', $userId)->count()),
            'videos'      => $count(fn () => DB::table('videos')->where('user_id', $userId)->count()),
            'nfts_minted' => $count(fn () => DB::table('nfts')->where('user_id', $userId)->count()),
            'nfts_owned'  => $count(fn () => DB::table('nfts')
                ->where('user_id', $userId)
                ->whereIn('status', ['minted', 'listed', 'sold', 'transferred'])
                ->count()),
            'coins_held'  => $count(fn () => DB::table('creator_coin_balances')
                ->where('user_id', $userId)->where('balance', '>', 0)->count()),
            'purchases'   => $count(fn () => DB::table('transactions')
                ->where('sender_user_id', $userId)
                ->where('status', 'approved')
                ->count()),
        ];
    }

    /**
     * Progress toward a single achievement: [current, target, percent 0-100].
     */
    public static function progressFor(array $achievement, array $stats): array
    {
        $target = max(1, (int) ($achievement['target'] ?? 1));
        $current = (int) ($stats[$achievement['metric'] ?? ''] ?? 0);
        $pct = (int) min(100, round($current / $target * 100));

        return [min($current, $target), $target, $pct];
    }

    /**
     * Grant any achievements the user now qualifies for. XP is applied
     * in-memory; the caller is responsible for persisting the user.
     */
    public static function checkAndUnlockAchievements(User $user)
    {
        $already = UserAchievement::where('user_id', $user->id)->pluck('achievement_key')->all();

        $all = self::achievements();

        // Nothing left to earn — skip the stats queries entirely.
        if (count($already) >= count($all)) {
            return;
        }

        $stats = self::userStats($user);

        foreach ($all as $key => $a) {
            if (in_array($key, $already, true)) {
                continue;
            }
            $met = false;
            try {
                [$current, $target] = self::progressFor($a, $stats);
                $met = $current >= $target;
            } catch (\Throwable $e) {
                $met = false;
            }
            if ($met) {
                UserAchievement::create([
                    'user_id' => $user->id,
                    'achievement_key' => $key,
                    'unlocked_at' => Carbon::now(),
                ]);
                self::applyXp($user, $a['xp'] ?? 0);

                // Badge XP has to reach the ledger too, or "XP today" silently under-reports
                // every unlock -- and unlocks are some of the largest single awards there are.
                try {
                    \App\Models\XpEvent::create([
                        'user_id' => $user->id,
                        'action' => 'achievement',
                        'xp' => (int) ($a['xp'] ?? 0),
                        'sparks' => 0,
                        'subject_type' => 'achievement',
                        'subject_id' => $key,
                    ]);
                } catch (\Throwable $e) {
                    // xp_events not migrated yet — the unlock itself still stands
                }

                self::$events[] = ['type' => 'achievement', 'name' => $a['name'], 'icon' => $a['icon']];
            }
        }
    }

    /**
     * Award XP for an action (posting, tipping, etc.) and persist.
     */
    public static function addXp(User $user, $amount)
    {
        self::applyXp($user, (int) $amount);
        $user->save();
    }

    protected static function applyXp(User $user, $amount)
    {
        $user->xp = (int) ($user->xp ?? 0) + max(0, (int) $amount);
        $user->level = intdiv($user->xp, self::XP_PER_LEVEL) + 1;
    }
}
