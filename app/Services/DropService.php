<?php

namespace App\Services;

use App\Model\User;
use App\Models\UserDrop;
use App\Models\UserReward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Drops -- the loot box layer.
 *
 * Two deliberate constraints on the design:
 *
 * 1. Drops are earned, or bought with sparks (which are themselves only earnable).
 *    There is no path from a credit card to a drop. A randomised box sold for money is
 *    a gambling product under Belgian and Dutch law, is under active scrutiny in the UK
 *    and several US states, and the card networks treat it as high risk -- on a site
 *    that already has adult-content processing constraints that is not a fight worth
 *    picking. Earned boxes carry none of that exposure.
 *
 * 2. Nothing in the reward table can be exchanged back into money. Rewards are XP,
 *    sparks, streak insurance and cosmetics. That keeps the box outside "prize of
 *    monetary value", which is the other half of most gambling definitions.
 *
 * The roll happens server-side at the moment of opening, never at grant time, so the
 * contents cannot be read out of the database ahead of the reveal animation.
 */
class DropService
{
    /** Rarity weights per box tier. Values are relative, they do not need to total 100. */
    public const TIERS = [
        'bronze' => ['label' => 'Bronze Drop', 'icon' => '🥉', 'cost' => 60,
                     'weights' => ['common' => 700, 'rare' => 250, 'epic' => 45,  'legendary' => 5]],
        'silver' => ['label' => 'Silver Drop', 'icon' => '🥈', 'cost' => 150,
                     'weights' => ['common' => 500, 'rare' => 350, 'epic' => 130, 'legendary' => 20]],
        'gold'   => ['label' => 'Gold Drop',   'icon' => '🥇', 'cost' => 320,
                     'weights' => ['common' => 250, 'rare' => 450, 'epic' => 250, 'legendary' => 50]],
    ];

    /**
     * Rolls without a rare-or-better before one is guaranteed.
     *
     * A pity counter is the difference between "random" and "feels rigged". Without it
     * a genuinely fair 25% chance still hands some users eight commons in a row and they
     * conclude the box is broken.
     */
    public const PITY_THRESHOLD = 9;

    public const RARITY_ORDER = ['common', 'rare', 'epic', 'legendary'];

    /**
     * The reward table.
     *
     * Everything here is deliverable end-to-end today: sparks and XP move real balances,
     * streak freezes are consumed by the streak logic, flair is stored in the inventory
     * and rendered on the profile. Nothing is a placeholder that does nothing when
     * granted.
     */
    public static function rewards(): array
    {
        return [
            // Common
            'sparks_25'     => ['rarity' => 'common',    'icon' => '✨', 'name' => '25 Sparks',        'type' => 'sparks', 'amount' => 25],
            'sparks_40'     => ['rarity' => 'common',    'icon' => '✨', 'name' => '40 Sparks',        'type' => 'sparks', 'amount' => 40],
            'xp_75'         => ['rarity' => 'common',    'icon' => '⚡', 'name' => '75 XP',            'type' => 'xp',     'amount' => 75],
            'xp_120'        => ['rarity' => 'common',    'icon' => '⚡', 'name' => '120 XP',           'type' => 'xp',     'amount' => 120],

            // Rare
            'sparks_120'    => ['rarity' => 'rare',      'icon' => '💫', 'name' => '120 Sparks',       'type' => 'sparks', 'amount' => 120],
            'xp_400'        => ['rarity' => 'rare',      'icon' => '🔋', 'name' => '400 XP',           'type' => 'xp',     'amount' => 400],
            'streak_freeze' => ['rarity' => 'rare',      'icon' => '🧊', 'name' => 'Streak Freeze',    'type' => 'freeze', 'amount' => 1],

            // Epic
            'sparks_400'    => ['rarity' => 'epic',      'icon' => '🌟', 'name' => '400 Sparks',       'type' => 'sparks', 'amount' => 400],
            'xp_1200'       => ['rarity' => 'epic',      'icon' => '🚀', 'name' => '1,200 XP',         'type' => 'xp',     'amount' => 1200],
            'freeze_pack'   => ['rarity' => 'epic',      'icon' => '❄️', 'name' => 'Freeze Pack (3)',  'type' => 'freeze', 'amount' => 3],
            'flair_ember'   => ['rarity' => 'epic',      'icon' => '🔥', 'name' => 'Ember Frame',      'type' => 'flair',  'amount' => 1],
            'flair_neon'    => ['rarity' => 'epic',      'icon' => '💠', 'name' => 'Neon Frame',       'type' => 'flair',  'amount' => 1],

            // Legendary
            'sparks_1000'   => ['rarity' => 'legendary', 'icon' => '👑', 'name' => '1,000 Sparks',     'type' => 'sparks', 'amount' => 1000],
            'flair_aurora'  => ['rarity' => 'legendary', 'icon' => '🌌', 'name' => 'Aurora Frame',     'type' => 'flair',  'amount' => 1],
            'flair_crown'   => ['rarity' => 'legendary', 'icon' => '👑', 'name' => 'Crown Frame',      'type' => 'flair',  'amount' => 1],
        ];
    }

    /** Which box tier a level-up should hand out. */
    public static function tierForLevel(int $level): string
    {
        if ($level >= 15) {
            return 'gold';
        }
        if ($level >= 5) {
            return 'silver';
        }

        return 'bronze';
    }

    /** Give a user an unopened drop. */
    public static function grant(User $user, string $tier, string $source = 'quest'): ?UserDrop
    {
        try {
            if (!isset(self::TIERS[$tier])) {
                $tier = 'bronze';
            }

            $drop = UserDrop::create([
                'user_id' => $user->id,
                'tier' => $tier,
                'source' => $source,
            ]);

            GamificationService::pushEvent([
                'type' => 'drop',
                'name' => self::TIERS[$tier]['label'],
                'icon' => self::TIERS[$tier]['icon'],
            ]);

            return $drop;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Buy a drop with sparks. Returns the new drop, or null if the balance is short. */
    public static function buyWithSparks(User $user, string $tier): ?UserDrop
    {
        if (!isset(self::TIERS[$tier])) {
            return null;
        }

        if (!EngagementService::spendSparks($user, (int) self::TIERS[$tier]['cost'])) {
            return null;
        }

        return self::grant($user, $tier, 'purchase_sparks');
    }

    /**
     * Open a drop: roll it, apply the reward, and record what came out.
     *
     * @return array|null  the reward definition plus its key, or null if not openable
     */
    public static function open(User $user, int $dropId): ?array
    {
        try {
            return DB::transaction(function () use ($user, $dropId) {
                // Locked and re-checked so a double-submitted open cannot roll twice.
                $drop = UserDrop::where('id', $dropId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$drop || $drop->opened_at !== null) {
                    return null;
                }

                $locked = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$locked) {
                    return null;
                }

                $pity = (int) ($locked->drop_pity ?? 0);
                $rarity = self::rollRarity($drop->tier, $pity);

                // Pity resets the moment it pays out, and only counts common rolls.
                $locked->drop_pity = $rarity === 'common' ? $pity + 1 : 0;

                $rewardKey = self::pickReward($rarity);
                $reward = self::rewards()[$rewardKey];

                self::applyReward($locked, $rewardKey, $reward);
                $locked->save();

                // Mirror onto the caller's instance.
                $user->xp = $locked->xp;
                $user->level = $locked->level;
                $user->sparks = $locked->sparks;
                $user->streak_freezes = $locked->streak_freezes;
                $user->drop_pity = $locked->drop_pity;

                $drop->opened_at = Carbon::now();
                $drop->reward_key = $rewardKey;
                $drop->reward_rarity = $rarity;
                $drop->reward_payload = json_encode($reward);
                $drop->save();

                return array_merge($reward, ['key' => $rewardKey, 'tier' => $drop->tier]);
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Weighted rarity roll, with the pity floor applied. */
    protected static function rollRarity(string $tier, int $pity): string
    {
        $weights = self::TIERS[$tier]['weights'] ?? self::TIERS['bronze']['weights'];

        // Pity reached: drop `common` out of the running entirely, so the roll is
        // guaranteed rare or better.
        if ($pity >= self::PITY_THRESHOLD) {
            unset($weights['common']);
        }

        $total = array_sum($weights);
        if ($total <= 0) {
            return 'rare';
        }

        $roll = random_int(1, $total);
        foreach ($weights as $rarity => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $rarity;
            }
        }

        return array_key_first($weights);
    }

    /** Pick one reward uniformly from those of the rolled rarity. */
    protected static function pickReward(string $rarity): string
    {
        $candidates = array_keys(array_filter(
            self::rewards(),
            fn ($r) => $r['rarity'] === $rarity
        ));

        if (empty($candidates)) {
            return 'sparks_25';
        }

        return $candidates[random_int(0, count($candidates) - 1)];
    }

    /**
     * Apply a reward to the (already locked) user.
     * Caller is responsible for saving.
     */
    protected static function applyReward(User $user, string $key, array $reward): void
    {
        $amount = (int) ($reward['amount'] ?? 0);

        switch ($reward['type']) {
            case 'sparks':
                $user->sparks = (int) ($user->sparks ?? 0) + $amount;
                $user->lifetime_sparks = (int) ($user->lifetime_sparks ?? 0) + $amount;
                break;

            case 'xp':
                $before = (int) ($user->level ?? 1);
                $user->xp = (int) ($user->xp ?? 0) + $amount;
                $user->level = intdiv((int) $user->xp, GamificationService::XP_PER_LEVEL) + 1;
                // XP out of a box can cross a level boundary. Announce it like any other
                // level-up, otherwise the level silently changes behind the reveal.
                if ((int) $user->level > $before) {
                    GamificationService::pushEvent(['type' => 'level', 'level' => (int) $user->level]);
                }
                break;

            case 'freeze':
                $user->streak_freezes = (int) ($user->streak_freezes ?? 0) + $amount;
                break;

            case 'flair':
                // Cosmetics live in the inventory, not on the user row, so the set can
                // grow without another migration each time.
                //
                // Deliberately not updateOrCreate with a DB::raw increment: on the create
                // branch the raw expression would be inserted as the column value.
                $row = UserReward::firstOrNew(['user_id' => $user->id, 'reward_key' => $key]);
                $row->quantity = (int) ($row->quantity ?? 0) + 1;
                $row->save();
                break;
        }
    }

    /** A user's unopened drops. */
    public static function unopened(User $user)
    {
        try {
            return UserDrop::where('user_id', $user->id)->unopened()->orderBy('created_at')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /** Rolls remaining before the guaranteed rare-or-better. */
    public static function pityRemaining(User $user): int
    {
        return max(0, self::PITY_THRESHOLD - (int) ($user->drop_pity ?? 0));
    }
}
