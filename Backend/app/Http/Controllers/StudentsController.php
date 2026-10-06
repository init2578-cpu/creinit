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
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isDirecteur = $user ? $user->hasRole('Directeur') : false;

        $studentsQuery = User::role('Apprenant')
            ->with(['studentGroups.module', 'exerciseSubmissions', 'particularModules'])
            ->orderBy('name');

        if (!$isDirecteur) {
            $studentsQuery->where('is_particulier', false);
        }

        $students = $studentsQuery->get()
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
                    'is_particulier' => (bool) ($user->is_particulier ?? false),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'groups' => $user->studentGroups->map(fn($group) => [
                        'id' => $group->id,
                        'nom_groupe' => $group->nom_groupe,
                        'module' => $group->module?->titre ?? $group->module?->nom_module ?? 'N/A',
                    ]),
                    'particular_modules' => $user->particularModules->map(fn($m) => [
                        'id' => $m->id,
                        'titre' => $m->titre,
                        'code_module' => $m->code_module,
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

        $modules = \App\Models\Module::where('is_active', true)
            ->select('id', 'titre', 'code_module')
            ->orderBy('titre')
            ->get();

        return Inertia::render('Scolarite/StudentsIndex', [
            'students' => $students,
            'modules' => $modules,
            'is_directeur' => $isDirecteur,
        ]);
    }

    /**
     * Store a newly created learner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            // Profile fields
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'dernier_diplome' => 'nullable|string|max:255',
            'sexe' => 'nullable|string|in:M,F',
            'is_particulier' => 'nullable|boolean',
            'particular_module_ids' => 'nullable|array',
            'particular_module_ids.*' => 'exists:modules,id',
        ]);

        $existingErrors = ApplicationController::isPhoneOrEmailRegistered(
            $validated['telephone'] ?? null,
            $validated['email'] ?? null
        );
        if (!empty($existingErrors)) {
            return back()->withErrors($existingErrors)->withInput();
        }

        $isDirecteur = $request->user()?->hasRole('Directeur');
        $isParticulier = $isDirecteur && !empty($validated['is_particulier']);
        $moduleIds = $isParticulier ? ($validated['particular_module_ids'] ?? []) : [];

        DB::transaction(function() use ($validated, $isParticulier, $moduleIds) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? 'password'),
                'telephone' => $validated['telephone'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => true,
                'is_particulier' => $isParticulier,
            ]);

            $user->assignRole('Apprenant');

            if ($isParticulier && !empty($moduleIds)) {
                $user->particularModules()->sync($moduleIds);
            }

            // Create Application profile
            Application::create([
                'user_id' => $user->id,
                'nom_complet' => $user->name,
                'telephone' => $user->telephone,
                'adresse_reelle' => $user->adresse,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                'niveau_etude' => $validated['niveau_etude'] ?? null,
                'dernier_diplome_libelle' => $validated['dernier_diplome'] ?? null,
                'sexe' => $validated['sexe'] ?? 'M',
                'status' => 'admitted', // Manual creation implies admission
            ]);
        });

        $message = $isParticulier
            ? 'Apprenant particulier créé sous la supervision exclusive du Directeur.'
            : 'Apprenant créé avec succès.';

        return back()->with('success', $message);
    }

    /**
     * Update the specified learner.
     */
    public function update(Request $request, User $student)
    {
        $isDirecteur = $request->user()?->hasRole('Directeur');

        if ($student->is_particulier && !$isDirecteur) {
            abort(403, 'Accès interdit : Cet apprenant est sous la supervision exclusive du Directeur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => 'nullable|string|min:8',
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            // Profile fields
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'dernier_diplome' => 'nullable|string|max:255',
            'sexe' => 'nullable|string|in:M,F',
            'is_particulier' => 'nullable|boolean',
            'particular_module_ids' => 'nullable|array',
            'particular_module_ids.*' => 'exists:modules,id',
        ]);

        $appId = Application::where('user_id', $student->id)->value('id');
        $existingErrors = ApplicationController::isPhoneOrEmailRegistered(
            $validated['telephone'] ?? null,
            $validated['email'] ?? null,
            ignoreUserId: $student->id,
            ignoreAppId: $appId ? (int)$appId : null
        );
        if (!empty($existingErrors)) {
            return back()->withErrors($existingErrors)->withInput();
        }

        DB::transaction(function() use ($validated, $student, $isDirecteur) {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : $student->is_active,
            ];

            if ($isDirecteur && isset($validated['is_particulier'])) {
                $updateData['is_particulier'] = (bool) $validated['is_particulier'];
            }

            $student->update($updateData);

            if (!empty($validated['password'])) {
                $student->update(['password' => Hash::make($validated['password'])]);
            }

            if ($isDirecteur && isset($validated['is_particulier'])) {
                if ($validated['is_particulier']) {
                    $student->particularModules()->sync($validated['particular_module_ids'] ?? []);
                } else {
                    $student->particularModules()->detach();
                }
            }

            // Update or Create Application profile
            Application::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'nom_complet' => $student->name,
                    'telephone' => $student->telephone,
                    'adresse_reelle' => $student->adresse,
                    'date_naissance' => $validated['date_naissance'] ?? null,
                    'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                    'niveau_etude' => $validated['niveau_etude'] ?? null,
                    'dernier_diplome_libelle' => $validated['dernier_diplome'] ?? null,
                    'sexe' => $validated['sexe'] ?? 'M',
                    'status' => 'admitted',
                ]
            );
        });

        return back()->with('success', 'Profil apprenant mis à jour.');
    }

    /**
     * Remove the specified learner.
     */
    public function destroy(Request $request, User $student)
    {
        if ($student->is_particulier && !$request->user()?->hasRole('Directeur')) {
            abort(403, 'Accès interdit : Cet apprenant est sous la supervision exclusive du Directeur.');
        }

        DB::transaction(function() use ($student) {
            // Delete associated application/profile
            Application::where('user_id', $student->id)->delete();
            $student->particularModules()->detach();
            $student->particularExams()->detach();
            $student->delete();
        });

        return back()->with('success', 'Apprenant supprimé avec succès.');
    }
}
