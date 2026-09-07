<?php

namespace App\Http\Controllers;

use App\Models\UserReward;
use App\Services\DropService;
use App\Services\EngagementService;
use App\Services\GamificationService;
use App\Services\QuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The rewards hub: the daily loop in one place -- streak, quests, drops, inventory.
 */
class EngagementController extends Controller
{
    public function hub()
    {
        $me = Auth::user();

        $board = QuestService::board($me);
        $drops = DropService::unopened($me);

        $inventory = collect();
        try {
            $inventory = UserReward::where('user_id', $me->id)->active()->get();
        } catch (\Throwable $e) {
            $inventory = collect();
        }

        return view('gamification.rewards', [
            'me' => $me,
            'board' => $board,
            'quests' => QuestService::POOL,
            'drops' => $drops,
            'tiers' => DropService::TIERS,
            'inventory' => $inventory,
            'rewards' => DropService::rewards(),
            'today' => EngagementService::todaySummary($me),
            'progress' => EngagementService::levelProgress($me),
            'pity' => DropService::pityRemaining($me),
            'pityThreshold' => DropService::PITY_THRESHOLD,
            'achievementCount' => $this->achievementCount($me),
        ]);
    }

    /** Claim a finished quest. */
    public function claimQuest(Request $request, $questId)
    {
        $me = Auth::user();
        $result = QuestService::claim($me, (int) $questId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => __('That quest is not ready to claim.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'reward' => $result,
            'balance' => $this->balance($me),
            'events' => GamificationService::pullEvents(),
        ]);
    }

    /**
     * Open a drop. The roll happens here, on open -- not when the drop was granted --
     * so nothing about the contents exists before the reveal.
     */
    public function openDrop(Request $request, $dropId)
    {
        $me = Auth::user();
        $reward = DropService::open($me, (int) $dropId);

        if (!$reward) {
            return response()->json([
                'success' => false,
                'message' => __('That drop has already been opened.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'reward' => $reward,
            'balance' => $this->balance($me),
            'pity' => DropService::pityRemaining($me),
        ]);
    }

    /** Buy a drop with sparks. */
    public function buyDrop(Request $request)
    {
        $me = Auth::user();
        $tier = (string) $request->input('tier', 'bronze');

        if (!isset(DropService::TIERS[$tier])) {
            return response()->json(['success' => false, 'message' => __('Unknown drop.')], 422);
        }

        $drop = DropService::buyWithSparks($me, $tier);

        if (!$drop) {
            return response()->json([
                'success' => false,
                'message' => __('Not enough sparks.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'drop' => ['id' => $drop->id, 'tier' => $drop->tier],
            'balance' => $this->balance($me),
        ]);
    }

    /** The numbers the header shows, so a JSON response can refresh them in place. */
    protected function balance($me): array
    {
        $progress = EngagementService::levelProgress($me);

        return [
            'xp' => (int) ($me->xp ?? 0),
            'level' => (int) ($me->level ?? 1),
            'sparks' => (int) ($me->sparks ?? 0),
            'freezes' => (int) ($me->streak_freezes ?? 0),
            'streak' => (int) ($me->streak_count ?? 0),
            'percent' => $progress['percent'],
        ];
    }

    protected function achievementCount($me): int
    {
        try {
            return \App\Models\UserAchievement::where('user_id', $me->id)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
