<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('roles')->orderBy('name')->get()->map(function($user) {
                $lastSeen = $user->last_login_at
                    ? \Illuminate\Support\Carbon::parse($user->last_login_at)
                        ->timezone('Africa/Dakar')
                        ->format('d/m/Y H:i')
                    : 'Jamais';

                $timeSpentSeconds = $user->time_spent ?? 0;
                if ($timeSpentSeconds < 60) {
                    $formattedTimeSpent = $timeSpentSeconds . 's';
                } elseif ($timeSpentSeconds < 3600) {
                    $minutes = floor($timeSpentSeconds / 60);
                    $seconds = $timeSpentSeconds % 60;
                    $formattedTimeSpent = "{$minutes}m {$seconds}s";
                } else {
                    $hours = floor($timeSpentSeconds / 3600);
                    $minutes = floor(($timeSpentSeconds % 3600) / 60);
                    $formattedTimeSpent = "{$hours}h {$minutes}m";
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'adresse' => $user->adresse,
                    'roles' => $user->getRoleNames(),
                    'profile_photo_url' => $user->profile_photo_url,
                    'is_active' => $user->is_active ?? true,
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'last_seen' => $lastSeen,
                    'time_spent' => $formattedTimeSpent,
                ];
            }),
            'available_roles' => Role::pluck('name')->toArray(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|exists:roles,name',
            'telephone'=> 'nullable|string|max:20|unique:users,telephone',
            'adresse'  => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'telephone'=> $validated['telephone'] ?? null,
            'adresse'  => $validated['adresse'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return back()->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role'     => 'required|string|exists:roles,name',
            'is_active'=> 'required|boolean',
            'telephone'=> ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'adresse'  => 'nullable|string|max:255',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Don't allow self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}
