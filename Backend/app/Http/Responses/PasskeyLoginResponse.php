<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user) {
            // Record last login timestamp
            $user->forceFill(['last_login_at' => now()])->save();
        }

        // Determine the correct route based on user roles
        $route = 'dashboard.director'; // Default
        if ($user) {
            if ($user->isTrainer()) {
                $route = 'trainer.groups';
            } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
                $route = 'student.dashboard';
            }
        }

        $redirectUrl = route($route);

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => $redirectUrl,
            ], 200);
        }

        return redirect()->to($redirectUrl);
    }
}
