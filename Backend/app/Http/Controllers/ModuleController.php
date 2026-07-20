<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Chapter;
use App\Models\Formation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModuleController extends Controller
{
    /**
     * Display a listing of the modules.
     */
    public function index(): Response
    {
        return Inertia::render('Scolarite/ModulesIndex', [
            'modules' => Module::withCount(['chapters' => function ($q) {
                $q->whereNull('exercise_type');
            }])->get(),
            'modules_detailed' => Module::with([
                'phases.chapters' => function ($q) {
                    $q->whereNull('exercise_type')->orderBy('ordre');
                },
                'chapters' => function ($q) {
                    $q->whereNull('exercise_type')->with('phase')->orderBy('ordre');
                }
            ])->get(),
            'predefined_formations' => Formation::all(['code', 'titre']),
        ]);
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasRole('Formateur') || $request->user()->isTrainer()) {
            abort(403, "Vous n'êtes pas autorisé à créer une formation.");
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'code_module' => 'required|string|max:50|unique:modules,code_module',
            'description' => 'nullable|string',
            'quota_heures' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        Module::create($validated);

        return back()->with('success', 'Le module a été créé avec succès.');
    }

    /**
     * Update the specified module in storage.
     */
    public function update(Request $request, Module $module): RedirectResponse
    {
        if ($request->user()->hasRole('Formateur') || $request->user()->isTrainer()) {
            abort(403, "Vous n'êtes pas autorisé à modifier une formation.");
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'code_module' => 'required|string|max:50|unique:modules,code_module,' . $module->id,
            'description' => 'nullable|string',
            'quota_heures' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $module->update($validated);

        return back()->with('success', 'Le module a été mis à jour.');
    }

    /**
     * Remove the specified module from storage.
     */
    public function destroy(Module $module): RedirectResponse
    {
        if (request()->user()->hasRole('Formateur') || request()->user()->isTrainer()) {
            abort(403, "Vous n'êtes pas autorisé à supprimer une formation.");
        }

        $module->delete();

        return back()->with('success', 'Le module a été supprimé.');
    }

    /**
     * Manage chapters of a module.
     */
    public function storeChapter(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'phase_id' => 'nullable|exists:phases,id',
            'objectif_pedagogique' => 'nullable|string',
            'materiels_necessaires' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'video_position' => ['nullable', 'string', 'regex:/^(before|after|after_p_\d+)$/'],
            'is_published' => 'nullable|boolean',
        ]);

        if (!isset($validated['ordre'])) {
            $validated['ordre'] = $module->chapters()->count() + 1;
        }

        // Add default values for required exercise fields
        $validated['exercise_points'] = 20.00;
        $validated['exercise_type'] = 'none';
        $validated['is_approved'] = $request->user()->hasRole('Directeur');

        // Un chapitre ne peut pas être publié au public tant que sa validation reste en attente
        if (!$validated['is_approved']) {
            $validated['is_published'] = false;
        } else {
            $validated['is_published'] = $request->boolean('is_published');
        }

        $chapter = $module->chapters()->create($validated);

        if (!$validated['is_approved']) {
            $directeurs = \App\Models\User::role('Directeur')->get();
            foreach ($directeurs as $directeur) {
                $directeur->notify(new \App\Notifications\ChapterProposedNotification($chapter, $request->user()));
            }
        }

        return back()->with('success', 'Le chapitre a été ajouté.');
    }

    /**
     * Remove a chapter.
     */
    public function destroyChapter(Chapter $chapter): RedirectResponse
    {
        $chapter->delete();

        return back()->with('success', 'Le chapitre a été supprimé.');
    }

    /**
     * Update a chapter.
     */
    public function updateChapter(Request $request, Chapter $chapter): RedirectResponse
    {
        $rawRequest = $request->all();
        \Illuminate\Support\Facades\Log::info('Updating chapter raw:', $rawRequest);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'phase_id' => 'nullable|exists:phases,id',
            'objectif_pedagogique' => 'nullable|string',
            'materiels_necessaires' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
            'video_position' => ['nullable', 'string', 'regex:/^(before|after|after_p_\d+)$/'],
            'is_published' => 'nullable|boolean',
            'attachments' => 'nullable|array',
        ]);

        \Illuminate\Support\Facades\Log::info('Updating chapter validated:', $validated);

        if (!isset($validated['ordre'])) {
            unset($validated['ordre']);
        }

        // Handle File Uploads
        $currentAttachments = $chapter->attachments ?? [];
        if ($request->hasFile('new_attachments')) {
            $files = $request->file('new_attachments');
            foreach ($files as $file) {
                $path = $file->store('chapters/attachments/' . $chapter->id, 'private');
                $currentAttachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getClientOriginalExtension(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }
        
        $validated['attachments'] = $currentAttachments;

        // Force content if it was validated but somehow lost
        if (array_key_exists('content', $validated)) {
             $chapter->content = $validated['content'];
        }

        if (!$request->user()->hasRole('Directeur')) {
            $validated['is_approved'] = false;
        }

        // Determine approval state: if user is not Director, force false
        $isApproved = array_key_exists('is_approved', $validated) 
            ? (bool) $validated['is_approved'] 
            : (bool) $chapter->is_approved;

        if (!$request->user()->hasRole('Directeur')) {
            $isApproved = false;
        }

        // Un chapitre ne peut pas être publié si sa validation est en attente
        if (!$isApproved) {
            if ($request->has('is_published') && $request->boolean('is_published')) {
                return back()->with('error', 'Un chapitre ne peut pas être publié au public tant que sa validation reste en attente par la direction.');
            }
            $validated['is_published'] = false;
        }

        $chapter->update($validated);

        if (!$request->user()->hasRole('Directeur')) {
            $directeurs = \App\Models\User::role('Directeur')->get();
            foreach ($directeurs as $directeur) {
                $directeur->notify(new \App\Notifications\ChapterProposedNotification($chapter, $request->user()));
            }
        }

        return back()->with('success', 'Le chapitre a été mis à jour avec succès.');
    }

    /**
     * Download a chapter attachment.
     */
    public function downloadAttachment(Chapter $chapter, int $index)
    {
        $attachments = $chapter->attachments;
        if (!isset($attachments[$index])) {
            abort(404);
        }

        $attachment = $attachments[$index];
        $path = Storage::disk('private')->path($attachment['path']);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => Storage::disk('private')->mimeType($attachment['path']),
            'Content-Disposition' => 'inline; filename="' . $attachment['name'] . '"'
        ]);
    }

    /**
     * Remove a specific attachment.
     */
    public function destroyAttachment(Chapter $chapter, int $index): RedirectResponse
    {
        $attachments = $chapter->attachments;
        if (isset($attachments[$index])) {
            Storage::disk('private')->delete($attachments[$index]['path']);
            array_splice($attachments, $index, 1);
            
            $updateData = ['attachments' => $attachments];
            if (!\Illuminate\Support\Facades\Auth::user()->hasRole('Directeur')) {
                $updateData['is_approved'] = false;
                $updateData['is_published'] = false;
            }
            $chapter->update($updateData);
        }

        return back()->with('success', 'La pièce jointe a été supprimée.');
    }

    public function reorderChapters(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'chapters' => 'required|array',
            'chapters.*.id' => 'required|exists:chapters,id',
            'chapters.*.ordre' => 'required|integer',
        ]);

        foreach ($validated['chapters'] as $chapterData) {
            Chapter::where('id', $chapterData['id'])
                ->where('module_id', $module->id)
                ->update(['ordre' => $chapterData['ordre']]);
        }

        return back()->with('success', 'L\'ordre des chapitres a été mis à jour.');
    }

    public function toggleApproveChapter(Chapter $chapter): RedirectResponse
    {
        $newApproval = !$chapter->is_approved;
        $updateData = ['is_approved' => $newApproval];

        // Si la validation est annulée / remise en attente, dépublier automatiquement le chapitre
        if (!$newApproval) {
            $updateData['is_published'] = false;
        }

        $chapter->update($updateData);
        $status = $newApproval ? 'validé' : 'remis en attente et masqué du public';
        return back()->with('success', "Le chapitre a été {$status} avec succès.");
    }

    /**
     * Store a new phase in a module.
     */
    public function storePhase(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quota_heures' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'ordre' => 'nullable|integer',
        ]);

        if (!isset($validated['ordre'])) {
            $validated['ordre'] = $module->phases()->count() + 1;
        }

        $module->phases()->create($validated);

        return back()->with('success', 'La phase a été créée.');
    }

    /**
     * Update an existing phase.
     */
    public function updatePhase(Request $request, \App\Models\Phase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quota_heures' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'ordre' => 'nullable|integer',
        ]);

        $phase->update($validated);

        return back()->with('success', 'La phase a été mise à jour.');
    }

    /**
     * Delete a phase.
     */
    public function destroyPhase(\App\Models\Phase $phase): RedirectResponse
    {
        $phase->delete();

        return back()->with('success', 'La phase a été supprimée.');
    }

    /**
     * Reorder phases of a module.
     */
    public function reorderPhases(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.id' => 'required|exists:phases,id',
            'phases.*.ordre' => 'required|integer',
        ]);

        foreach ($validated['phases'] as $phaseData) {
            \App\Models\Phase::where('id', $phaseData['id'])
                ->where('module_id', $module->id)
                ->update(['ordre' => $phaseData['ordre']]);
        }

        return back()->with('success', 'L\'ordre des phases a été mis à jour.');
    }
}
