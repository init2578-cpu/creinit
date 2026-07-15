<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        if (Auth::check()) {
            $user = Auth::user();
            $route = 'dashboard.director'; // Default
            if ($user->isTrainer()) {
                $route = 'trainer.groups';
            } elseif ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
                $route = 'dashboard.director';
            } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
                $route = 'student.dashboard';
            }
            return redirect()->route($route);
        }
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

        $loginInput = $request->login;
        $cleanPhone = str_replace(' ', '', $loginInput);

        $user = \App\Models\User::where('email', $loginInput)
            ->orWhere('telephone', $loginInput)
            ->orWhere(\Illuminate\Support\Facades\DB::raw("REPLACE(telephone, ' ', '')"), $cleanPhone)
            ->first();

        if (!$user || !Auth::attempt(['id' => $user->id, 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => "Ces identifiants ne correspondent pas à nos enregistrements.",
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => "Votre compte a été désactivé. Veuillez contacter l'administration.",
            ]);
        }

        if ($user->roles->isEmpty()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => "Votre candidature est en cours de traitement. Vous pourrez vous connecter dès qu'elle sera validée.",
            ]);
        }

        // Record last login timestamp
        $user->forceFill(['last_login_at' => now()])->save();

        $route = 'dashboard.director'; // Default

        if ($user->isTrainer()) {
            $route = 'trainer.groups';
        } elseif ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $route = 'dashboard.director';
        } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
            $route = 'student.dashboard';
        }

        // Audit login
        AuditLog::write(
            event: 'login',
            description: "{$user->name} s'est connecté(e)",
            userId: $user->id,
        );

        return redirect()->route($route);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // Audit logout before destroying session
        $userId = Auth::user()?->id;
        $userName = Auth::user()?->name ?? 'Utilisateur';

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        AuditLog::write(
            event: 'logout',
            description: "{$userName} s'est déconnecté(e)",
            userId: $userId,
        );

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
        if ($user->isTrainer()) {
            $route = 'trainer.groups';
        } elseif ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $route = 'dashboard.director';
        } elseif ($user->hasRole('Apprenant') || $user->hasRole('Stagiaire')) {
            $route = 'student.dashboard';
        }

        return redirect()->route($route)->with('success', 'Votre mot de passe a été mis à jour avec succès.');
    }
}
