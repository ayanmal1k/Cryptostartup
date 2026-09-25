<?php

namespace App\Services;

use App\Model\User;
use App\Model\Wallet;
use App\Models\UserReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The paid side of the rewards hub. Money buys fixed, known items — never a chance.
 *
 * What is deliberately NOT for sale: spins, game plays, drops, sparks and XP. Sparks buy
 * drops and XP levels grant drops, so selling either would open a path from a card
 * payment to a random prize, which is what gambling and loot-box rules (and the card
 * networks) look for. Streak freezes and cosmetic frames are exactly what they say, with
 * nothing random attached.
 *
 * Paid from platform credits (the wallet), locked and debited in one transaction like
 * every other credit purchase on the site.
 */
class PerkShopService
{
    public const PERKS = [
        'freeze_1' => [
            'label' => 'Streak Freeze', 'icon' => '🧊', 'price' => 0.99,
            'blurb' => 'Miss a day without losing your streak.',
            'grant' => ['type' => 'freeze', 'amount' => 1],
        ],
        'freeze_3' => [
            'label' => '3 Streak Freezes', 'icon' => '❄️', 'price' => 1.99,
            'blurb' => 'Cover a long weekend. Best value.', 'tag' => 'Best value',
            'grant' => ['type' => 'freeze', 'amount' => 3],
        ],
        'frame_ember' => [
            'label' => 'Ember Frame', 'icon' => '🔥', 'price' => 2.99,
            'blurb' => 'A glowing ring around your profile picture.',
            'grant' => ['type' => 'frame', 'key' => 'flair_ember'],
        ],
        'frame_neon' => [
            'label' => 'Neon Frame', 'icon' => '💠', 'price' => 2.99,
            'blurb' => 'Electric blue and pink, everywhere your picture shows.',
            'grant' => ['type' => 'frame', 'key' => 'flair_neon'],
        ],
        'frame_aurora' => [
            'label' => 'Aurora Frame', 'icon' => '🌌', 'price' => 4.99,
            'blurb' => 'Northern-lights colours on your profile picture.',
            'grant' => ['type' => 'frame', 'key' => 'flair_aurora'],
        ],
        'frame_crown' => [
            'label' => 'Crown Frame', 'icon' => '👑', 'price' => 6.99,
            'blurb' => 'Solid gold. For the regulars.',
            'grant' => ['type' => 'frame', 'key' => 'flair_crown'],
        ],
    ];

    /** Frame reward keys and how they look (used by the profile and rewards views). */
    public const FRAMES = ['flair_ember', 'flair_neon', 'flair_aurora', 'flair_crown'];

    public static function owns(User $user, string $rewardKey): bool
    {
        try {
            return UserReward::where('user_id', $user->id)->where('reward_key', $rewardKey)
                ->where('quantity', '>', 0)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Buy a perk with platform credits. Returns a short success message. */
    public static function buy(User $user, string $perkKey): string
    {
        $perk = self::PERKS[$perkKey] ?? null;
        if (!$perk) {
            throw new RuntimeException('That item is not available.');
        }
        $price = round((float) $perk['price'], 2);

        return DB::transaction(function () use ($user, $perkKey, $perk, $price) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();

            if ($perk['grant']['type'] === 'frame' && self::owns($locked, $perk['grant']['key'])) {
                throw new RuntimeException('You already own this frame.');
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || (float) $wallet->total < $price) {
                throw new RuntimeException('Not enough credits — this costs $' . number_format($price, 2)
                    . '. Add funds to your wallet and try again.');
            }
            $wallet->update(['total' => round((float) $wallet->total - $price, 2)]);

            if ($perk['grant']['type'] === 'freeze') {
                $locked->streak_freezes = (int) ($locked->streak_freezes ?? 0) + (int) $perk['grant']['amount'];
                $user->streak_freezes = $locked->streak_freezes;
                $message = $perk['grant']['amount'] > 1
                    ? $perk['grant']['amount'] . ' streak freezes added.'
                    : 'Streak freeze added.';
            } else {
                $row = UserReward::firstOrNew(['user_id' => $user->id, 'reward_key' => $perk['grant']['key']]);
                $row->quantity = max(1, (int) ($row->quantity ?? 0));
                $row->save();
                // A frame you just bought is the frame you want to see.
                $locked->equipped_frame = $perk['grant']['key'];
                $user->equipped_frame = $locked->equipped_frame;
                $message = $perk['label'] . ' is on your profile picture now.';
            }
            $locked->save();

            DB::table('perk_purchases')->insert([
                'user_id' => $user->id,
                'perk_key' => $perkKey,
                'price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $message;
        });
    }

    /** Equip a frame the user owns, or pass null to take it off. */
    public static function equip(User $user, ?string $frameKey): void
    {
        if ($frameKey !== null) {
            if (!in_array($frameKey, self::FRAMES, true) || !self::owns($user, $frameKey)) {
                throw new RuntimeException('You do not own that frame.');
            }
        }
        $user->equipped_frame = $frameKey;
        $user->save();
    }

    /** CSS class for a user's equipped frame, or '' when none (or no longer owned). */
    public static function frameClass($user): string
    {
        $key = $user->equipped_frame ?? null;

        return $key && in_array($key, self::FRAMES, true) ? 'has-frame frame-' . Str::after($key, 'flair_') : '';
    }
}
