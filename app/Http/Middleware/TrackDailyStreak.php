<?php

namespace App\Http\Middleware;

use App\Services\GamificationService;
use Closure;
use Illuminate\Support\Facades\Auth;

class TrackDailyStreak
{
    /**
     * Tick the logged-in user's daily streak.
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
        }

        return $next($request);
    }
}
