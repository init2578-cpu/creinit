<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainerOrStaff
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Allow Directeur, Secrétaire, Formateur, or a Stagiaire with course_assistant type (isTrainer())
        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire') || $user->isTrainer()) {
            return $next($request);
        }

        abort(403, 'User does not have the right roles.');
    }
}
