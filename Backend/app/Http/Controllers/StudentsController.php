<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Application;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentsController extends Controller
{
    /**
     * Display a listing of all learners (apprenants).
     */
    public function index(): Response
    {
        $students = User::role('Apprenant')
            ->with(['studentGroups.module', 'exerciseSubmissions'])
            ->orderBy('name')
            ->get()
            ->map(function($user) {
                // Get application data
                $application = Application::where('user_id', $user->id)->first();
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'telephone' => $user->telephone,
                    'adresse' => $user->adresse,
                    'is_active' => $user->is_active ?? true,
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'groups' => $user->studentGroups->map(fn($group) => [
                        'id' => $group->id,
                        'nom_groupe' => $group->nom_groupe,
                        'module' => $group->module->nom_module ?? 'N/A',
                    ]),
                    'profile' => $application ? [
                        'date_naissance' => $application->date_naissance,
                        'lieu_naissance' => $application->lieu_naissance,
                        'niveau_etude' => $application->niveau_etude,
                        'dernier_diplome' => $application->dernier_diplome_libelle,
                        'fonction' => $application->fonction,
                        'etablissement' => $application->etablissement,
                        'sexe' => $application->sexe,
                    ] : null,
                    'submissions_count' => $user->exerciseSubmissions->count(),
                ];
            });

        return Inertia::render('Scolarite/StudentsIndex', [
            'students' => $students,
        ]);
    }

    /**
     * Store a newly created learner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            // Profile fields
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'dernier_diplome' => 'nullable|string|max:255',
            'sexe' => 'nullable|string|in:M,F',
        ]);

        DB::transaction(function() use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? 'password'),
                'telephone' => $validated['telephone'],
                'adresse' => $validated['adresse'],
                'is_active' => true,
            ]);

            $user->assignRole('Apprenant');

            // Create Application profile
            Application::create([
                'user_id' => $user->id,
                'nom_complet' => $user->name,
                'telephone' => $user->telephone,
                'adresse_reelle' => $user->adresse,
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'niveau_etude' => $validated['niveau_etude'],
                'dernier_diplome_libelle' => $validated['dernier_diplome'],
                'sexe' => $validated['sexe'],
                'status' => 'admitted', // Manual creation implies admission
            ]);
        });

        return back()->with('success', 'Apprenant créé avec succès.');
    }

    /**
     * Update the specified learner.
     */
    public function update(Request $request, User $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($student->id)],
            'password' => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            // Profile fields
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'dernier_diplome' => 'nullable|string|max:255',
            'sexe' => 'nullable|string|in:M,F',
        ]);

        DB::transaction(function() use ($validated, $student) {
            $student->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                'adresse' => $validated['adresse'],
                'is_active' => $validated['is_active'],
            ]);

            if (!empty($validated['password'])) {
                $student->update(['password' => Hash::make($validated['password'])]);
            }

            // Update or Create Application profile
            Application::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'nom_complet' => $student->name,
                    'telephone' => $student->telephone,
                    'adresse_reelle' => $student->adresse,
                    'date_naissance' => $validated['date_naissance'],
                    'lieu_naissance' => $validated['lieu_naissance'],
                    'niveau_etude' => $validated['niveau_etude'],
                    'dernier_diplome_libelle' => $validated['dernier_diplome'],
                    'sexe' => $validated['sexe'],
                    'status' => 'admitted',
                ]
            );
        });

        return back()->with('success', 'Profil apprenant mis à jour.');
    }

    /**
     * Remove the specified learner.
     */
    public function destroy(User $student)
    {
        DB::transaction(function() use ($student) {
            // Delete associated application/profile
            Application::where('user_id', $student->id)->delete();
            $student->delete();
        });

        return back()->with('success', 'Apprenant supprimé avec succès.');
    }
}
