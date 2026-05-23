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
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');
        
        $query = User::with(['roles', 'permissions', 'application']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $lowSearch = strtolower((string)$search);
                $q->whereRaw('LOWER(name) like ?', ["%{$lowSearch}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$lowSearch}%"])
                  ->orWhereRaw('LOWER(telephone) like ?', ["%{$lowSearch}%"]);
            });
        }

        if ($role) {
            $query->role($role);
        }

        if ($status !== null && $status !== '') {
            $isActive = $status === 'active' || $status === '1';
            $query->where(function($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        return Inertia::render('Users/Index', [
            'users' => $query->orderBy('name')->get()->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'adresse' => $user->adresse ?: $user->application?->adresse_reelle,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getPermissionNames(),
                'profile_photo_url' => $user->profile_photo_url,
                'is_active' => $user->is_active ?? true,
                'created_at' => $user->created_at->format('d/m/Y'),
            ]),
            'filters' => $request->only(['search', 'role', 'status']),
            'available_roles' => Role::pluck('name')->toArray(),
            'available_permissions' => \Spatie\Permission\Models\Permission::pluck('name')->toArray(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users',
            'password'    => 'required|string|min:8',
            'roles'       => 'required|array',
            'roles.*'     => 'exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
            'telephone'   => 'required|string|max:20',
            'adresse'     => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $userData = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'adresse'   => $validated['adresse'] ?? null,
            'password'  => Hash::make($validated['password']),
        ];

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $userData['profile_photo_path'] = $path;
        }

        $user = User::create($userData);

        $user->syncRoles($validated['roles']);
        if (!empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'      => 'nullable|string|min:8',
            'roles'         => 'required|array',
            'roles.*'       => 'exists:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
            'is_active'     => 'required|boolean',
            'telephone'     => 'required|string|max:20',
            'adresse'       => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'adresse'   => $validated['adresse'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? []);

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
