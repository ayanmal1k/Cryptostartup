<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Models\UserAchievement;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    /**
     * Leaderboard — top users by XP or by current streak.
     */
    public function leaderboard(Request $request)
    {
        $tab = in_array($request->get('tab'), ['streaks', 'level'], true) ? $request->get('tab') : 'xp';
        $me = Auth::user();

        $rows = collect();
        $myRank = null;
        $total = 0;
        try {
            if ($tab === 'streaks') {
                $rows = User::orderByDesc('streak_count')->orderByDesc('xp')->take(50)->get();
                $myRank = User::where('streak_count', '>', (int) ($me->streak_count ?? 0))->count() + 1;
            } elseif ($tab === 'level') {
                $rows = User::orderByDesc('level')->orderByDesc('xp')->take(50)->get();
                $myRank = User::where('level', '>', (int) ($me->level ?? 1))->count() + 1;
            } else {
                $rows = User::orderByDesc('xp')->take(50)->get();
                $myRank = User::where('xp', '>', (int) ($me->xp ?? 0))->count() + 1;
            }
            $total = User::count();
        } catch (\Throwable $e) {
            // Gamification columns not migrated yet — render an empty board rather than 500.
            $rows = collect();
            $myRank = null;
        }

        return view('gamification.leaderboard', compact('rows', 'tab', 'myRank', 'me', 'total'));
    }

    /**
     * The user's achievements page — all badges with locked/unlocked state.
     */
    public function achievements()
    {
        $all = GamificationService::achievements();
        $me = Auth::user();

        $unlocked = collect();
        try {
            $unlocked = UserAchievement::where('user_id', Auth::id())->get()->keyBy('achievement_key');
        } catch (\Throwable $e) {
            // Table not migrated yet — show everything as locked.
            $unlocked = collect();
        }

        // Real progress toward everything still locked, so the page shows "4 / 7 days"
        // rather than just a greyed-out card with no sense of how close you are.
        $stats = [];
        try {
            $stats = GamificationService::userStats($me);
        } catch (\Throwable $e) {
            $stats = [];
        }

        $progress = [];
        foreach ($all as $key => $a) {
            [$current, $target, $pct] = GamificationService::progressFor($a, $stats);
            $progress[$key] = ['current' => $current, 'target' => $target, 'pct' => $pct];
        }

        // Group by category for display, preserving the declared category order.
        $grouped = [];
        foreach (GamificationService::CATEGORIES as $catKey => $catLabel) {
            $items = array_filter($all, fn ($a) => ($a['category'] ?? 'start') === $catKey);
            if (!empty($items)) {
                $grouped[$catKey] = ['label' => $catLabel, 'items' => $items];
            }
        }

        $totalXp = array_sum(array_map(
            fn ($k) => $unlocked->has($k) ? (int) ($all[$k]['xp'] ?? 0) : 0,
            array_keys($all)
        ));

        return view('gamification.achievements', compact('all', 'unlocked', 'progress', 'grouped', 'me', 'totalXp'));
    }
}
