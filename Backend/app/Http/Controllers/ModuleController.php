<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Chapter;
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
            'modules' => Module::withCount('chapters')->get(),
            'modules_detailed' => Module::with('chapters')->get(),
        ]);
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(Request $request): RedirectResponse
    {
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
            'ordre' => 'nullable|integer',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'is_published' => 'nullable|boolean',
        ]);

        if (!isset($validated['ordre'])) {
            $validated['ordre'] = $module->chapters()->count() + 1;
        }

        // Add default values for required exercise fields
        $validated['exercise_points'] = 20.00;
        $validated['exercise_type'] = 'none';
        $validated['is_approved'] = $request->user()->hasRole('Directeur');

        $module->chapters()->create($validated);

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
            'ordre' => 'nullable|integer',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
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

        $chapter->update($validated);

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
            $chapter->update(['attachments' => $attachments]);
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
        $chapter->update(['is_approved' => !$chapter->is_approved]);
        $status = $chapter->is_approved ? 'validé' : 'remis en attente';
        return back()->with('success', "Le chapitre a été {$status} avec succès.");
    }
}
