<?php

namespace App\Http\Middleware;

use App\Services\DropService;
use App\Services\GamificationService;
use App\Services\QuestService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class TrackDailyStreak
{
    /**
     * Tick the logged-in user's daily streak and prepare the nav badges.
     *
     * Wrapped in a guard so gamification can never break a page load
     * (e.g. before the migration has been run on the server).
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            try {
                GamificationService::touchDailyStreak(Auth::user());
                $events = GamificationService::pullEvents();
                if (!empty($events) && $request->hasSession()) {
                    // Show the celebration on THIS page load only.
                    $request->session()->now('gamification_celebrations', $events);
                }
            } catch (\Throwable $e) {
                // Silently ignore — never interrupt the request.
            }

            // The nav badge count. Computed once here and shared with every view rather
            // than queried inside each menu partial -- this app renders up to four
            // separate navs per page, and each would otherwise run its own query.
            try {
                $summary = QuestService::summary(Auth::user());
                $drops = DropService::unopened(Auth::user())->count();

                View::share('gmQuestsClaimable', (int) $summary['claimable']);
                View::share('gmUnopenedDrops', $drops);
                View::share('gmRewardsBadge', (int) $summary['claimable'] + $drops);
            } catch (\Throwable $e) {
                View::share('gmQuestsClaimable', 0);
                View::share('gmUnopenedDrops', 0);
                View::share('gmRewardsBadge', 0);
            }
        }

        return $next($request);
    }
}
