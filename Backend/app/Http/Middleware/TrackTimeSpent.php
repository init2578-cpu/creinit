<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackTimeSpent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = time();
            $lastRequestAt = session('last_request_at');

            if ($lastRequestAt) {
                $difference = $now - $lastRequestAt;
                // Only count active browsing: session difference between 1 second and 15 minutes (900 seconds)
                if ($difference > 0 && $difference < 900) {
                    $user->increment('time_spent', $difference);
                }
            }

            session(['last_request_at' => $now]);
        }

        return $next($request);
    }
}
