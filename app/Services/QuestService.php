<?php

namespace App\Services;

use App\Model\User;
use App\Models\UserQuest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Daily quests: three small, achievable tasks that reset every day.
 *
 * This is the part that creates a reason to open the app tomorrow. A streak counter
 * alone only rewards the act of loading a page; a quest board gives the visit a shape
 * ("two comments and I've cleared it") and an end point, which is what makes the loop
 * feel finishable rather than endless.
 *
 * The set is drawn deterministically from (user id, date) so it is stable for the whole
 * day without needing to be stored at generation time -- refreshing the page cannot
 * reroll a board into something easier.
 */
class QuestService
{
    /**
     * The quest pool. `action` maps to an EngagementService action key; `target` is how
     * many times it must happen. Rewards are deliberately modest per quest -- the sweep
     * bonus for clearing all three is where the real payout sits.
     */
    public const POOL = [
        'watch_5'      => ['action' => 'video_watch',    'target' => 5,  'xp' => 20, 'sparks' => 8,  'icon' => '📺', 'label' => 'Watch 5 videos',            'audience' => 'all'],
        'watch_10'     => ['action' => 'video_watch',    'target' => 10, 'xp' => 35, 'sparks' => 14, 'icon' => '🍿', 'label' => 'Watch 10 videos',           'audience' => 'all'],
        'react_10'     => ['action' => 'reaction_give',  'target' => 10, 'xp' => 15, 'sparks' => 6,  'icon' => '❤️', 'label' => 'Like 10 posts',             'audience' => 'all'],
        'comment_2'    => ['action' => 'comment_create', 'target' => 2,  'xp' => 25, 'sparks' => 10, 'icon' => '💬', 'label' => 'Leave 2 comments',          'audience' => 'all'],
        'comment_5'    => ['action' => 'comment_create', 'target' => 5,  'xp' => 45, 'sparks' => 18, 'icon' => '🗣️', 'label' => 'Leave 5 comments',          'audience' => 'all'],
        'explore_3'    => ['action' => 'profile_visit',  'target' => 3,  'xp' => 15, 'sparks' => 6,  'icon' => '🧭', 'label' => 'Explore 3 creators',        'audience' => 'all'],
        'follow_1'     => ['action' => 'follow',         'target' => 1,  'xp' => 20, 'sparks' => 8,  'icon' => '➕', 'label' => 'Follow a new creator',      'audience' => 'all'],
        'stream_join'  => ['action' => 'stream_join',    'target' => 1,  'xp' => 30, 'sparks' => 12, 'icon' => '📡', 'label' => 'Join a live stream',        'audience' => 'all'],

        // Creator-flavoured. Only offered to accounts that have actually published.
        'post_1'       => ['action' => 'post_create',    'target' => 1,  'xp' => 40, 'sparks' => 15, 'icon' => '📝', 'label' => 'Publish a post',            'audience' => 'creator'],
        'upload_1'     => ['action' => 'video_upload',   'target' => 1,  'xp' => 55, 'sparks' => 20, 'icon' => '🎬', 'label' => 'Upload a video',            'audience' => 'creator'],
        'go_live'      => ['action' => 'stream_start',   'target' => 1,  'xp' => 70, 'sparks' => 25, 'icon' => '🔴', 'label' => 'Go live',                   'audience' => 'creator'],
    ];

    /** How many quests a board holds. */
    public const BOARD_SIZE = 3;

    /**
     * Today's board for a user, generating it on first call of the day.
     *
     * @return \Illuminate\Support\Collection<UserQuest>
     */
    public static function board(User $user)
    {
        try {
            $today = Carbon::today()->toDateString();

            $existing = UserQuest::where('user_id', $user->id)
                ->whereDate('quest_date', $today)
                ->get();

            if ($existing->count() >= self::BOARD_SIZE) {
                return $existing;
            }

            foreach (self::pickFor($user, $today) as $key) {
                $def = self::POOL[$key];
                UserQuest::firstOrCreate(
                    ['user_id' => $user->id, 'quest_key' => $key, 'quest_date' => $today],
                    ['target' => $def['target'], 'progress' => 0]
                );
            }

            return UserQuest::where('user_id', $user->id)->whereDate('quest_date', $today)->get();
        } catch (\Throwable $e) {
            // Table not migrated yet -- render an empty board rather than 500.
            return collect();
        }
    }

    /**
     * Choose today's quest keys.
     *
     * Seeded by user id + date so the same person gets the same board all day and a
     * different one tomorrow, with no stored "which board did we roll" state.
     */
    protected static function pickFor(User $user, string $date): array
    {
        $pool = array_keys(array_filter(
            self::POOL,
            fn ($q) => $q['audience'] === 'all' || self::isCreator($user)
        ));

        // mt_srand makes the shuffle reproducible for this (user, day) pair.
        mt_srand(crc32($user->id . '|' . $date));
        shuffle($pool);
        mt_srand();

        // Never hand out two quests that track the same action -- the second would
        // complete itself as a side effect of the first and feel broken.
        $picked = [];
        $seenActions = [];
        foreach ($pool as $key) {
            $action = self::POOL[$key]['action'];
            if (in_array($action, $seenActions, true)) {
                continue;
            }
            $seenActions[] = $action;
            $picked[] = $key;
            if (count($picked) >= self::BOARD_SIZE) {
                break;
            }
        }

        return $picked;
    }

    protected static function isCreator(User $user): bool
    {
        try {
            if (DB::table('posts')->where('user_id', $user->id)->exists()) {
                return true;
            }

            return DB::table('videos')->where('user_id', $user->id)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Advance any of today's quests that track this action.
     *
     * Called from EngagementService::award(), so quest progress and XP always move
     * together and cannot drift apart.
     */
    public static function recordAction(User $user, string $action, int $times = 1): void
    {
        try {
            $today = Carbon::today()->toDateString();

            $quests = UserQuest::where('user_id', $user->id)
                ->whereDate('quest_date', $today)
                ->whereNull('completed_at')
                ->get()
                ->filter(fn ($q) => (self::POOL[$q->quest_key]['action'] ?? null) === $action);

            foreach ($quests as $quest) {
                $quest->progress = min((int) $quest->target, (int) $quest->progress + $times);
                if ($quest->progress >= (int) $quest->target) {
                    $quest->completed_at = Carbon::now();
                    $def = self::POOL[$quest->quest_key];
                    GamificationService::pushEvent([
                        'type' => 'quest',
                        'name' => $def['label'],
                        'icon' => $def['icon'],
                    ]);
                }
                $quest->save();
            }
        } catch (\Throwable $e) {
            // never block the action that triggered this
        }
    }

    /**
     * Claim a completed quest's reward. Returns what was granted, or null if the quest
     * is not claimable (not finished, already claimed, not this user's, not today's).
     */
    public static function claim(User $user, int $questId): ?array
    {
        try {
            return DB::transaction(function () use ($user, $questId) {
                // Locked and re-checked inside the transaction: without this a double
                // click claims the same reward twice.
                $quest = UserQuest::where('id', $questId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$quest || !$quest->completed_at || $quest->claimed_at) {
                    return null;
                }

                $def = self::POOL[$quest->quest_key] ?? null;
                if (!$def) {
                    return null;
                }

                $quest->claimed_at = Carbon::now();
                $quest->save();

                $locked = User::where('id', $user->id)->lockForUpdate()->first();
                $locked->xp = (int) ($locked->xp ?? 0) + (int) $def['xp'];
                $locked->level = intdiv((int) $locked->xp, GamificationService::XP_PER_LEVEL) + 1;
                $locked->sparks = (int) ($locked->sparks ?? 0) + (int) $def['sparks'];
                $locked->lifetime_sparks = (int) ($locked->lifetime_sparks ?? 0) + (int) $def['sparks'];
                $locked->save();

                $user->xp = $locked->xp;
                $user->level = $locked->level;
                $user->sparks = $locked->sparks;

                // Claim rewards have to reach the ledger like every other award, or
                // "XP today" silently under-reports them -- a cleared board is one of
                // the biggest single payouts there is, so the omission is very visible.
                try {
                    \App\Models\XpEvent::create([
                        'user_id' => $user->id,
                        'action' => 'quest_claim',
                        'xp' => (int) $def['xp'],
                        'sparks' => (int) $def['sparks'],
                        'subject_type' => 'quest',
                        'subject_id' => $quest->quest_key,
                    ]);
                } catch (\Throwable $e) {
                    // xp_events missing — the claim itself still stands
                }

                return [
                    'xp' => (int) $def['xp'],
                    'sparks' => (int) $def['sparks'],
                    'label' => $def['label'],
                    'swept' => self::maybeSweep($user),
                ];
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * If every quest on today's board is now claimed, pay the sweep bonus and hand out
     * a drop. Idempotent -- the xp_events row for `quest_sweep` is what stops it paying
     * twice, via the action's daily cap of 1.
     */
    protected static function maybeSweep(User $user): bool
    {
        try {
            $today = Carbon::today()->toDateString();
            $quests = UserQuest::where('user_id', $user->id)->whereDate('quest_date', $today)->get();

            if ($quests->isEmpty() || $quests->contains(fn ($q) => $q->claimed_at === null)) {
                return false;
            }

            $result = EngagementService::award($user, 'quest_sweep');
            if ($result['xp'] === 0 && $result['sparks'] === 0) {
                return false; // already swept today
            }

            DropService::grant($user, 'gold', 'quest_sweep');
            GamificationService::pushEvent([
                'type' => 'sweep',
                'name' => 'Daily board cleared',
                'icon' => '🎯',
            ]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Summary counts for the board header and nav badge. */
    public static function summary(User $user): array
    {
        try {
            $board = self::board($user);

            return [
                'total' => $board->count(),
                'complete' => $board->filter(fn ($q) => $q->completed_at !== null)->count(),
                'claimable' => $board->filter(fn ($q) => $q->completed_at !== null && $q->claimed_at === null)->count(),
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'complete' => 0, 'claimable' => 0];
        }
    }
}
