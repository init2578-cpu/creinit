<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        if (! Auth::attempt([$loginField => $request->login, 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => "Ces identifiants ne correspondent pas à nos enregistrements.",
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Record last login timestamp
        $user->forceFill(['last_login_at' => now()])->save();

        $route = 'dashboard.director'; // Default

        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $route = 'dashboard.director';
        } elseif ($user->isTrainer()) {
            $route = 'trainer.groups';
        } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
            $route = 'student.dashboard';
        }

        return redirect()->intended(route($route));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Display the change password view.
     */
    public function changePassword(): \Inertia\Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    /**
     * Handle a password change request.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => "Le nouveau mot de passe doit être différent du mot de passe actuel.",
            ]);
        }

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        // Record last login timestamp
        $user->forceFill(['last_login_at' => now()])->save();

        $route = 'dashboard.director'; // Default
        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $route = 'dashboard.director';
        } elseif ($user->isTrainer()) {
            $route = 'trainer.groups';
        } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
            $route = 'student.dashboard';
        }

        return redirect()->intended(route($route))->with('success', 'Votre mot de passe a été mis à jour avec succès.');
    }
}
