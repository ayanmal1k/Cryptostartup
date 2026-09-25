<?php

namespace App\Services;

use App\Model\User;
use App\Models\XpEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The XP and spark economy.
 *
 * Before this existed the platform had XP columns and a level formula but nothing
 * that awarded XP except the daily login tick -- `GamificationService::addXp()` was
 * never called from anywhere. Levels therefore measured how many days you had opened
 * the site and nothing else, which is why the whole system felt inert.
 *
 * Everything funnels through award(). It is deliberately total-failure-safe: an
 * engagement reward must never be the reason a post fails to save.
 */
class EngagementService
{
    /**
     * The action catalogue.
     *
     * `cap` is the number of times per day the action can pay out. Without caps the
     * economy is farmable in a loop -- posting and deleting comments would be the
     * fastest route to the top of the leaderboard, which devalues every other number
     * on the page. Actions that already cost the user money (tips, subscriptions) get
     * generous caps; free repeatable actions get tight ones.
     */
    public const ACTIONS = [
        // Showing up
        'daily_visit'     => ['xp' => 10,  'sparks' => 5,  'cap' => 1,  'label' => 'Daily visit'],

        // Consuming — small, capped, keeps a casual scroller progressing
        'video_watch'     => ['xp' => 3,   'sparks' => 0,  'cap' => 15, 'label' => 'Watched a video'],
        'profile_visit'   => ['xp' => 1,   'sparks' => 0,  'cap' => 10, 'label' => 'Explored a creator'],
        'reaction_give'   => ['xp' => 2,   'sparks' => 0,  'cap' => 20, 'label' => 'Liked a post'],
        'follow'          => ['xp' => 5,   'sparks' => 1,  'cap' => 10, 'label' => 'Followed a creator'],
        'comment_create'  => ['xp' => 5,   'sparks' => 1,  'cap' => 10, 'label' => 'Left a comment'],

        // Creating
        'post_create'     => ['xp' => 25,  'sparks' => 3,  'cap' => 5,  'label' => 'Published a post'],
        'video_upload'    => ['xp' => 40,  'sparks' => 5,  'cap' => 5,  'label' => 'Uploaded a video'],
        'stream_start'    => ['xp' => 80,  'sparks' => 10, 'cap' => 3,  'label' => 'Went live'],
        'nft_mint'        => ['xp' => 70,  'sparks' => 10, 'cap' => 5,  'label' => 'Minted an NFT'],

        // Spending — the actions the business actually wants
        'tip_send'        => ['xp' => 60,  'sparks' => 10, 'cap' => 10, 'label' => 'Sent a tip'],
        'purchase'        => ['xp' => 50,  'sparks' => 8,  'cap' => 10, 'label' => 'Unlocked content'],
        'subscribe'       => ['xp' => 100, 'sparks' => 20, 'cap' => 5,  'label' => 'Subscribed to a creator'],
        'custom_request'  => ['xp' => 50,  'sparks' => 8,  'cap' => 5,  'label' => 'Ordered a custom request'],
        'stream_join'     => ['xp' => 10,  'sparks' => 2,  'cap' => 5,  'label' => 'Joined a live stream'],

        // Earning (creator side)
        'tip_receive'     => ['xp' => 30,  'sparks' => 5,  'cap' => 20, 'label' => 'Received a tip'],
        'sale_receive'    => ['xp' => 40,  'sparks' => 6,  'cap' => 20, 'label' => 'Made a sale'],

        // System-granted, uncapped because the quest layer already rate-limits them
        'quest_complete'  => ['xp' => 0,   'sparks' => 0,  'cap' => 0,  'label' => 'Quest complete'],
        'quest_sweep'     => ['xp' => 50,  'sparks' => 25, 'cap' => 1,  'label' => 'Cleared every daily quest'],
    ];

    /** Events queued this request for the celebration toasts. */
    protected static $events = [];

    public static function pullEvents(): array
    {
        $events = self::$events;
        self::$events = [];

        return $events;
    }

    public static function pushEvent(array $event): void
    {
        self::$events[] = $event;
    }

    /**
     * Award XP and sparks for an action.
     *
     * @param  User        $user
     * @param  string      $action  a key from ACTIONS
     * @param  mixed       $subject optional model the award relates to (for the audit trail)
     * @param  int         $times   award multiple units at once (e.g. bulk backfill)
     * @return array{xp:int,sparks:int,capped:bool,level_up:bool}
     */
    public static function award(User $user, string $action, $subject = null, int $times = 1): array
    {
        $none = ['xp' => 0, 'sparks' => 0, 'capped' => false, 'level_up' => false];

        try {
            $def = self::ACTIONS[$action] ?? null;
            if (!$def || $times < 1) {
                return $none;
            }

            // A zero-value action is a marker (quest_complete) -- nothing to pay out.
            if ((int) $def['xp'] === 0 && (int) $def['sparks'] === 0) {
                return $none;
            }

            if (self::isCapped($user, $action, (int) $def['cap'])) {
                return array_merge($none, ['capped' => true]);
            }

            $xp = (int) $def['xp'] * $times;
            $sparks = (int) $def['sparks'] * $times;

            $oldLevel = (int) ($user->level ?? 1);

            DB::transaction(function () use ($user, $action, $xp, $sparks, $subject) {
                // Row-lock the user so two concurrent requests cannot both read the same
                // balance and write back a total that loses one of the awards.
                $locked = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$locked) {
                    return;
                }

                $locked->xp = (int) ($locked->xp ?? 0) + $xp;
                $locked->level = intdiv((int) $locked->xp, GamificationService::XP_PER_LEVEL) + 1;
                $locked->sparks = (int) ($locked->sparks ?? 0) + $sparks;
                $locked->lifetime_sparks = (int) ($locked->lifetime_sparks ?? 0) + $sparks;
                $locked->save();

                // Mirror onto the in-memory instance the caller holds, so the rest of the
                // request sees the new totals without a refresh.
                $user->xp = $locked->xp;
                $user->level = $locked->level;
                $user->sparks = $locked->sparks;
                $user->lifetime_sparks = $locked->lifetime_sparks;

                XpEvent::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'xp' => $xp,
                    'sparks' => $sparks,
                    'subject_type' => $subject ? get_class($subject) : null,
                    'subject_id' => $subject && isset($subject->id) ? (string) $subject->id : null,
                ]);
            });

            $levelUp = (int) $user->level > $oldLevel;
            if ($levelUp) {
                self::pushEvent(['type' => 'level', 'level' => (int) $user->level]);
                GamificationService::pushEvent(['type' => 'level', 'level' => (int) $user->level]);
                // A level-up is the natural moment to hand out a drop: it is earned,
                // predictable enough to chase, and rare enough to still feel like an event.
                DropService::grant($user, DropService::tierForLevel((int) $user->level), 'level_up');
            }

            // Actions feed the quest board.
            QuestService::recordAction($user, $action, $times);

            // Newly-qualifying badges.
            try {
                GamificationService::checkAndUnlockAchievements($user);
                $user->save();
            } catch (\Throwable $e) {
                // never block on badges
            }

            return ['xp' => $xp, 'sparks' => $sparks, 'capped' => false, 'level_up' => $levelUp];
        } catch (\Throwable $e) {
            // An engagement reward must never break the action that earned it.
            return $none;
        }
    }

    /**
     * Has this user already hit today's payout limit for this action?
     * A cap of 0 means "no payout"; a negative cap means uncapped.
     */
    protected static function isCapped(User $user, string $action, int $cap): bool
    {
        if ($cap < 0) {
            return false;
        }
        if ($cap === 0) {
            return true;
        }

        $used = XpEvent::where('user_id', $user->id)
            ->where('action', $action)
            ->where('created_at', '>=', Carbon::today())
            ->count();

        return $used >= $cap;
    }

    /**
     * Spend sparks. Returns false (without spending) if the balance is short.
     * Row-locked so a double-submit cannot spend the same sparks twice.
     */
    public static function spendSparks(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        try {
            return (bool) DB::transaction(function () use ($user, $amount) {
                $locked = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$locked || (int) ($locked->sparks ?? 0) < $amount) {
                    return false;
                }

                $locked->sparks = (int) $locked->sparks - $amount;
                $locked->save();
                $user->sparks = $locked->sparks;

                return true;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Total XP and sparks earned today, for the "today so far" strip. */
    public static function todaySummary(User $user): array
    {
        try {
            $row = XpEvent::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::today())
                ->selectRaw('COALESCE(SUM(xp),0) as xp, COALESCE(SUM(sparks),0) as sparks, COUNT(*) as actions')
                ->first();

            return [
                'xp' => (int) ($row->xp ?? 0),
                'sparks' => (int) ($row->sparks ?? 0),
                'actions' => (int) ($row->actions ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['xp' => 0, 'sparks' => 0, 'actions' => 0];
        }
    }

    /**
     * XP into the current level and XP needed for the next one, for progress bars.
     *
     * @return array{into:int,span:int,percent:int,next_level:int}
     */
    public static function levelProgress(User $user): array
    {
        $span = GamificationService::XP_PER_LEVEL;
        $xp = (int) ($user->xp ?? 0);
        $into = $xp % $span;

        return [
            'into' => $into,
            'span' => $span,
            'percent' => (int) round($into / $span * 100),
            'next_level' => (int) ($user->level ?? 1) + 1,
        ];
    }
}
