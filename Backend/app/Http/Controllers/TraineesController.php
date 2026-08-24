<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\InternshipRecord;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraineesController extends Controller
{
    /**
     * Display a listing of all trainees (stagiaires/assistants).
     */
    public function index(): Response
    {
        $trainees = User::role('Stagiaire')
            ->with(['internshipRecord.tutor'])
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'telephone' => $user->telephone,
                'adresse' => $user->adresse,
                'is_active' => $user->is_active ?? true,
                'created_at' => $user->created_at->format('d/m/Y'),
                'internship' => $user->internshipRecord ? [
                    'type' => $user->internshipRecord->internship_type,
                    'criteria' => $user->internshipRecord->criteria,
                    'start_date' => $user->internshipRecord->start_date?->format('Y-m-d'),
                    'end_date' => $user->internshipRecord->end_date?->format('Y-m-d'),
                    'profile_photo_url' => $user->profile_photo_url ?? null,
                    'status' => $user->internshipRecord->status,
                    'tuteur_id' => $user->internshipRecord->tuteur_id,
                    'tuteur' => $user->internshipRecord->tutor ? [
                        'id' => $user->internshipRecord->tutor->id,
                        'name' => $user->internshipRecord->tutor->name,
                    ] : null,
                    'motivation_letter' => $user->internshipRecord->motivation_letter_path ? Storage::url($user->internshipRecord->motivation_letter_path) : null,
                    'cni' => $user->internshipRecord->cni_path ? Storage::url($user->internshipRecord->cni_path) : null,
                    'cv' => $user->internshipRecord->cv_path ? Storage::url($user->internshipRecord->cv_path) : null,
                    'diploma' => $user->internshipRecord->diploma_path ? Storage::url($user->internshipRecord->diploma_path) : null,
                ] : null,
            ]);

        $trainers = User::role('Formateur')->get(['id', 'name']);
        $assistants = User::role('Stagiaire')
            ->whereHas('internshipRecord', function($q) {
                $q->where('internship_type', 'course_assistant');
            })
            ->get(['id', 'name'])
            ->map(function($user) {
                $user->name = $user->name . " (Assistant)";
                return $user;
            });
        $formateurs = $trainers->concat($assistants);

        return Inertia::render('Scolarite/TraineesIndex', [
            'trainees' => $trainees,
            'formateurs' => $formateurs,
        ]);
    }

    /**
     * Store a newly created trainee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            // Internship fields
            'internship_type' => 'required|string|in:course_assistant,course_substitute,management_assistant,secretary_assistant,director_assistant,field_internship',
            'tuteur_id' => 'nullable|exists:users,id',
            'criteria' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            // Documents
            'motivation_letter' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'cni' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'cv' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'diploma' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);

        $existingErrors = ApplicationController::isPhoneOrEmailRegistered(
            $validated['telephone'] ?? null,
            $validated['email'] ?? null
        );
        if (!empty($existingErrors)) {
            return back()->withErrors($existingErrors)->withInput();
        }

        DB::transaction(function() use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'],
                'adresse' => $validated['adresse'],
                'is_active' => true,
            ]);

            $user->assignRole('Stagiaire');

            if ($validated['internship_type'] === 'secretary_assistant') {
                $user->assignRole('Secrétaire');
            }

            // Handle file uploads
            $paths = [];
            foreach (['motivation_letter', 'cni', 'cv', 'diploma'] as $doc) {
                if ($request->hasFile($doc)) {
                    $paths[$doc . '_path'] = $request->file($doc)->store("trainees/{$user->id}", 'public');
                }
            }

            // Create Internship Record
            InternshipRecord::create(array_merge([
                'user_id' => $user->id,
                'internship_type' => $validated['internship_type'],
                'tuteur_id' => $validated['tuteur_id'] ?? null,
                'criteria' => $validated['criteria'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending', // Default status
            ], $paths));
        });

        return back()->with('success', 'Stagiaire créé avec succès (en attente).');
    }

    /**
     * Update the specified trainee.
     */
    public function update(Request $request, User $trainee)
    {
        // For multipart/form-data updates via POST (standard Inertia pattern)
        // ensure we check method spoofing if using PUT
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'is_active' => 'nullable',
            // Internship fields
            'internship_type' => 'required|string|in:course_assistant,course_substitute,management_assistant,secretary_assistant,director_assistant,field_internship',
            'tuteur_id' => 'nullable|exists:users,id',
            'criteria' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|in:pending,active,completed,terminated',
            // Documents
            'motivation_letter' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'cni' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'cv' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'diploma' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);

        $existingErrors = ApplicationController::isPhoneOrEmailRegistered(
            $validated['telephone'] ?? null,
            $validated['email'] ?? null,
            ignoreUserId: $trainee->id
        );
        if (!empty($existingErrors)) {
            return back()->withErrors($existingErrors)->withInput();
        }

        // is_active can come as string '1'/'0'/'true'/'false' or be absent (checkbox unchecked in FormData)
        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);

        DB::transaction(function() use ($validated, $trainee, $request, $isActive) {
            $trainee->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                'adresse' => $validated['adresse'],
                'is_active' => $isActive,
            ]);

            if (!empty($validated['password'])) {
                $trainee->update(['password' => Hash::make($validated['password'])]);
            }

            // Handle file updates
            $paths = [];
            foreach (['motivation_letter', 'cni', 'cv', 'diploma'] as $doc) {
                if ($request->hasFile($doc)) {
                    // Delete old file if exists
                    if ($trainee->internshipRecord && $trainee->internshipRecord->{$doc . '_path'}) {
                        Storage::disk('public')->delete($trainee->internshipRecord->{$doc . '_path'});
                    }
                    $paths[$doc . '_path'] = $request->file($doc)->store("trainees/{$trainee->id}", 'public');
                }
            }

            // Update or Create Internship Record
            InternshipRecord::updateOrCreate(
                ['user_id' => $trainee->id],
                array_merge([
                    'internship_type' => $validated['internship_type'],
                    'tuteur_id' => $validated['tuteur_id'] ?? null,
                    'criteria' => $validated['criteria'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => $validated['status'],
                ], $paths)
            );

            if ($validated['internship_type'] === 'secretary_assistant') {
                $trainee->assignRole('Secrétaire');
            } else {
                $trainee->removeRole('Secrétaire');
            }
        });

        return back()->with('success', 'Profil stagiaire mis à jour.');
    }

    /**
     * Remove the specified trainee.
     */
    public function destroy(User $trainee)
    {
        DB::transaction(function() use ($trainee) {
            // Delete files
            if ($trainee->internshipRecord) {
                Storage::disk('public')->deleteDirectory("trainees/{$trainee->id}");
                $trainee->internshipRecord->delete();
            }
            $trainee->delete();
        });

        return back()->with('success', 'Stagiaire supprimé avec succès.');
    }

    /**
     * Preview a trainee document.
     */
    public function previewFile(User $trainee, string $type): StreamedResponse
    {
        $record = $trainee->internshipRecord;
        if (!$record) abort(404);

        $path = match($type) {
            'motivation_letter' => $record->motivation_letter_path,
            'cni' => $record->cni_path,
            'cv' => $record->cv_path,
            'diploma' => $record->diploma_path,
            default => null
        };

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, "Document non trouvé.");
        }

        return Storage::disk('public')->response($path);
    }
}
