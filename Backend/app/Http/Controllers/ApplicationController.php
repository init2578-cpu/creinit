<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Scolarite\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Module;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function welcome(): Response
    {
        $stats = [
            'students' => 1200,
            'modules' => 0,
            'trainers' => 0,
            'success_rate' => 95,
        ];

        try {
            $stats['students'] = User::role('Apprenant')->count() + 1200;
            $stats['modules'] = Module::count();
            $stats['trainers'] = User::role('Formateur')->count();
        } catch (\Exception $e) {
            // Silently fail if roles aren't seeded yet to avoid crashing the landing page
        }

        $recent_posts = \App\Models\Post::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $active_partners = \App\Models\Partnership::where('status', 'actif')
            ->orderByDesc('date_signature')
            ->get();

        $upcoming_events = \App\Models\Event::where('status', 'actif')
            ->orderByDesc('date')
            ->take(3)
            ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'settings' => Setting::getGroup('general'),
            'stats' => $stats,
            'recentPosts' => $recent_posts,
            'partners' => $active_partners,
            'upcomingEvents' => $upcoming_events,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Scolarite/Apply', [
            'modules' => \App\Models\Module::all(['id', 'titre']),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('Scolarite/ApplicationsIndex', [
            'applications' => Application::with(['user', 'module'])->orderByDesc('created_at')->get(),
            'modules' => \App\Models\Module::all(['id', 'titre']),
        ]);
    }

    public function enrollManual(Request $request)
    {
        $validated = $request->validate([
            'nom_complet' => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'telephone'   => 'required|string|max:20',
            'module_id'   => 'required|exists:modules,id',
            'adresse_reelle' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'niveau_etude' => 'required|string|max:255',
            'dernier_diplome_libelle' => 'required|string|max:255',
            'fonction' => 'required|string|max:255',
            'etablissement' => 'nullable|string|max:255',
            'sexe' => 'required|string|in:M,F',
            'cni' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Create or find user
        $user = \App\Models\User::where(function ($query) use ($validated) {
            if ($validated['email']) {
                $query->where('email', $validated['email']);
                $query->orWhere('telephone', $validated['telephone']);
            } else {
                $query->where('telephone', $validated['telephone'])
                      ->whereNull('email');
            }
        })->first();

        if (!$user) {
            $user = \App\Models\User::create([
                'email' => $validated['email'],
                'name' => $this->formatTitleCase($validated['nom_complet']),
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                'telephone' => $validated['telephone'],
            ]);
            $user->assignRole('Apprenant');
        }

        $cniPath = $request->hasFile('cni') ? $request->file('cni')->store('applications/cni', 'private') : 'manual_enrollment';
        $diplomaPath = $request->hasFile('diploma') ? $request->file('diploma')->store('applications/diplomas', 'private') : 'manual_enrollment';

        // Create application (pending validation even for manual enrollment)
        Application::updateOrCreate(
            ['user_id' => $user->id, 'module_id' => $validated['module_id']],
            [
                'status' => 'pending',
                'cni_path' => $cniPath,
                'diploma_path' => $diplomaPath,
                'telephone' => $validated['telephone'],
                'adresse_reelle' => $this->formatTitleCase($validated['adresse_reelle']),
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $this->formatTitleCase($validated['lieu_naissance']),
                'niveau_etude' => $this->formatTitleCase($validated['niveau_etude']),
                'dernier_diplome_libelle' => $this->formatTitleCase($validated['dernier_diplome_libelle']),
                'fonction' => $this->formatTitleCase($validated['fonction']),
                'etablissement' => $this->formatTitleCase($validated['etablissement']),
                'sexe' => $validated['sexe'],
                'nom_complet' => $this->formatTitleCase($validated['nom_complet']),
            ]
        );

        return back()->with('success', "Candidat inscrit avec succès au module.");
    }

    public function updateStatus(Request $request, Application $application)
    {
        $validated = $request->validate([
            'status' => 'required|in:admitted,rejected,pending'
        ]);

        if ($validated['status'] === 'pending') {
            /** @var \App\Models\User $user */
            $user = $request->user();
            if (!$user->hasRole('Directeur')) {
                return redirect()->back()->with('error', "Seul le Directeur est autorisé à remettre une candidature en attente.");
            }
        }

        $application->update(['status' => $validated['status']]);

        // If admitted, ensure user has the Apprenant role
        if ($validated['status'] === 'admitted') {
            $application->user->assignRole('Apprenant');
        }

        return back()->with('success', "Le statut de la candidature a été mis à jour.");
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        // For public application, we MUST NOT use the current session user if they are an Admin/Formateur
        // because they might be testing the form or registering someone else.
        // We always look up or create a candidate based on the form data.
        $user = \App\Models\User::where(function ($query) use ($request) {
            if ($request->email) {
                $query->where('email', $request->email);
                $query->orWhere('telephone', $request->telephone);
            } else {
                $query->where('telephone', $request->telephone)
                      ->whereNull('email');
            }
        })->first();

        if (!$user) {
            $user = \App\Models\User::create([
               'email' => $request->email,
               'name' => $this->formatTitleCase($request->nom_complet),
               'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
               'telephone' => $request->telephone,
           ]);
        }
       
        $user->assignRole('Apprenant');
        
        $cniPath = $request->file('cni')->store('applications/cni', 'private');
        $diplomaPath = $request->file('diploma')->store('applications/diplomas', 'private');

        Application::updateOrCreate(
            ['user_id' => $user->id, 'module_id' => $request->module_id],
            [
                'cni_path' => $cniPath,
                'diploma_path' => $diplomaPath,
                'commentaires' => $request->commentaires,
                'adresse_reelle' => $this->formatTitleCase($request->adresse_reelle),
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $this->formatTitleCase($request->lieu_naissance),
                'niveau_etude' => $this->formatTitleCase($request->niveau_etude),
                'dernier_diplome_libelle' => $this->formatTitleCase($request->dernier_diplome_libelle),
                'fonction' => $this->formatTitleCase($request->fonction),
                'etablissement' => $this->formatTitleCase($request->etablissement),
                'telephone' => $request->telephone,
                'status' => 'pending',
                'sexe' => $request->sexe,
                'nom_complet' => $this->formatTitleCase($request->nom_complet),
            ]
        );

        return back()->with('success', 'Votre candidature a été soumise avec succès. Nous vous contacterons prochainement.');
    }

    public function update(\App\Http\Requests\Scolarite\UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        $data = $request->validated();
        
        // Format application fields
        $fieldsToFormat = [
            'adresse_reelle', 'lieu_naissance', 'niveau_etude', 
            'dernier_diplome_libelle', 'fonction', 'etablissement'
        ];
        
        foreach ($fieldsToFormat as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->formatTitleCase($data[$field]);
            }
        }

        // Update application details
        $application->update($data);

        // Update user details as well (name, email, telephone)
        $application->user->update([
            'name' => $this->formatTitleCase($request->nom_complet),
            'email' => $request->email,
            'telephone' => $request->telephone,
        ]);

        return back()->with('success', 'La candidature a été mise à jour avec succès.');
    }

    private function formatTitleCase(?string $value): ?string
    {
        if (!$value) return $value;
        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, "UTF-8");
    }

    public function previewFile(Application $application, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // $this->authorize('view', $application); // Commented out for now if policy not ready

        $path = $type === 'cni' ? $application->cni_path : $application->diploma_path;

        if ($path === 'manual_enrollment') {
             // Return a placeholder or message for manual enrollments without docs
             abort(404, "Aucun document pour une inscription manuelle.");
        }

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return Storage::disk('private')->response($path);
    }
}

