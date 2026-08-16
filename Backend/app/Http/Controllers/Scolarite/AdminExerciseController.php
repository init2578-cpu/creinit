<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ExerciseSubmission;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use App\Notifications\NewExerciseAvailableNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminExerciseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $exerciseQuery = Chapter::whereIn('exercise_type', ['online', 'file'])
            ->with(['module', 'questions.options'])
            ->orderBy('module_id')
            ->orderBy('ordre');

        $moduleQuery = Module::with('chapters.questions.options');

        // If Formateur (not Directeur), restrict to their groups' modules and students
        if (!$user->hasRole('Directeur') && $user->isTrainer()) {
            $myGroups = $user->groupsAsFormateur;
            $moduleIds = $myGroups->pluck('module_id');
            $studentIds = \Illuminate\Support\Facades\DB::table('group_user')
                ->whereIn('group_id', $myGroups->pluck('id'))
                ->pluck('user_id');

            if ($moduleIds->isNotEmpty()) {
                $exerciseQuery->whereIn('module_id', $moduleIds);
                $moduleQuery->whereIn('id', $moduleIds);
            }

            $exerciseQuery->with(['exerciseSubmissions' => function($q) use ($studentIds) {
                $q->whereIn('user_id', $studentIds)->with('user.studentGroups');
            }]);
        } else {
            $exerciseQuery->with('exerciseSubmissions.user.studentGroups');
        }

        return Inertia::render('Scolarite/ExercisesIndex', [
            'exercises' => $exerciseQuery->get(),
            'modules'   => $moduleQuery->get(),
        ]);
    }

    /**
     * Create a new exercise (by creating a new Chapter configured as an exercise).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'exercise_title' => 'required|string|max:255',
            'exercise_type' => 'required|in:online,file',
            'exercise_instructions' => 'nullable|string',
            'exercise_points' => 'required|numeric|min:0',
        ]);

        $module = Module::findOrFail($validated['module_id']);
        $ordre = $module->chapters()->max('ordre') + 1;

        $chapter = $module->chapters()->create([
            'titre' => $validated['exercise_title'],
            'content' => $validated['exercise_instructions'],
            'ordre' => $ordre,
            'exercise_type' => $validated['exercise_type'],
            'exercise_points' => $validated['exercise_points'],
            'exercise_title' => $validated['exercise_title'],
            'exercise_instructions' => $validated['exercise_instructions'],
            'is_published' => false,
            'is_approved' => $request->user()->hasRole('Directeur'),
        ]);

        return redirect()->back()->with('success', 'Exercice créé et sauvegardé en brouillon.');
    }

    public function togglePublish(Request $request, Chapter $chapter): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $updateData = [
            'is_published' => !$chapter->is_published,
        ];
        if (!$request->user()->hasRole('Directeur')) {
            $updateData['is_approved'] = false;
        }
        $chapter->update($updateData);

        if ($chapter->is_published) {
            $chapter->load('module');
            $students = User::role('Apprenant')
                ->whereHas('studentGroups', function ($query) use ($chapter) {
                    $query->where('module_id', $chapter->module_id);
                })->get();

            foreach ($students as $student) {
                $student->notify(new NewExerciseAvailableNotification($chapter));
            }
        }

        return redirect()->back()->with('success', 'Statut de publication mis à jour.');
    }

    /**
     * Update exercise settings for a chapter.
     */
    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'exercise_title' => 'required|string|max:255',
            'exercise_type' => 'required|in:online,file',
            'exercise_instructions' => 'nullable|string',
            'exercise_points' => 'required|integer|min:0',
        ]);

        $wasAlreadyExercise = in_array($chapter->exercise_type, ['online', 'file']);
        $currentPoints = $chapter->questions()->sum('points');
        if ($request->exercise_points < $currentPoints) {
            return redirect()->back()->with('error', "Impossible de réduire le barème : le total des points des questions existantes ({$currentPoints}) dépasse le nouveau barème ({$request->exercise_points}).");
        }

        if (!$request->user()->hasRole('Directeur')) {
            $validated['is_approved'] = false;
        }

        $chapter->update($validated);

        // Notify if it's a new exercise (first time exercise_type is set)
        if (!$wasAlreadyExercise && in_array($chapter->exercise_type, ['online', 'file'])) {
            $chapter->load('module');
            $students = User::role('Apprenant')
                ->whereHas('studentGroups', function ($query) use ($chapter) {
                    $query->where('module_id', $chapter->module_id);
                })->get();

            foreach ($students as $student) {
                $student->notify(new NewExerciseAvailableNotification($chapter));
            }
        }

        return redirect()->back()->with('success', 'Exercice mis à jour.');
    }

    public function destroy(Request $request, Chapter $chapter): RedirectResponse
    {
        if (!$request->user()->hasRole('Directeur')) {
            abort(403, 'Seul le directeur peut supprimer un exercice.');
        }

        // Delete associated files if any
        if (!empty($chapter->attachments)) {
            foreach ($chapter->attachments as $attachment) {
                \Illuminate\Support\Facades\Storage::disk('private')->delete($attachment['path']);
            }
        }

        // Delete questions and submissions (should be handled by DB cascade, but we can do it manually if needed)
        $chapter->questions()->delete();
        $chapter->exerciseSubmissions()->delete();
        
        $chapter->delete();

        return redirect()->back()->with('success', 'Exercice supprimé.');
    }

    public function updateAttachment(Request $request, Chapter $chapter): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $request->validate([
            'attachment' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('attachment');
        $path = $file->store('chapters/attachments/' . $chapter->id, 'private');
        
        $attachments = $chapter->attachments ?? [];
        $attachments[] = [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $file->getClientMimeType(),
        ];

        $updateData = ['attachments' => $attachments];
        if (!$request->user()->hasRole('Directeur')) {
            $updateData['is_approved'] = false;
        }
        $chapter->update($updateData);

        return redirect()->back()->with('success', 'Fichier ajouté avec succès.');
    }

    public function deleteAttachment(Request $request, Chapter $chapter, int $index): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $attachments = $chapter->attachments ?? [];
        if (isset($attachments[$index])) {
            \Illuminate\Support\Facades\Storage::disk('private')->delete($attachments[$index]['path']);
            array_splice($attachments, $index, 1);
            $updateData = ['attachments' => $attachments];
            if (!$request->user()->hasRole('Directeur')) {
                $updateData['is_approved'] = false;
            }
            $chapter->update($updateData);
        }

        return redirect()->back()->with('success', 'Fichier supprimé.');
    }

    public function gradeSubmission(Request $request, ExerciseSubmission $submission): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:' . ($submission->chapter->exercise_points ?? 20),
            'trainer_feedback' => 'nullable|string',
            'status' => 'required|in:graded,rejected,excellent,very_good,good,satisfactory,weak',
        ]);

        $submission->update($validated);

        // Notify student
        $submission->user->notify(new \App\Notifications\ExerciseGradedNotification($submission));

        return redirect()->back()->with('success', 'Exercice noté avec succès.');
    }

    /**
     * Manage Questions for an exercise.
     */
    public function storeQuestion(Request $request, Chapter $chapter): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'enonce' => 'required|string',
            'points' => 'required|numeric|min:0',
            'type' => 'required|in:qcm,open',
            'expected_answer' => 'nullable|string',
            'options' => 'array',
            'options.*.texte' => 'required_if:type,qcm|string',
            'options.*.is_correct' => 'required_if:type,qcm|boolean',
        ]);

        $currentPoints = $chapter->questions()->sum('points');
        if (($currentPoints + $validated['points']) > $chapter->exercise_points) {
            return redirect()->back()->with('error', "Le total des points des questions (" . ($currentPoints + $validated['points']) . ") ne peut pas dépasser le barème de l'exercice ({$chapter->exercise_points}).");
        }

        if (!$request->user()->hasRole('Directeur')) {
            $chapter->update(['is_approved' => false]);
        }

        $question = $chapter->questions()->create([
            'enonce' => $validated['enonce'],
            'points' => $validated['points'],
            'type' => $validated['type'],
            'expected_answer' => $validated['expected_answer'] ?? null,
            'ordre' => $chapter->questions()->count() + 1,
        ]);

        if ($validated['type'] === 'qcm' && !empty($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $question->options()->create($optionData);
            }
        }

        return redirect()->back();
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'points' => 'required|numeric|min:0',
            'enonce' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:qcm,open',
            'expected_answer' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|exists:options,id',
            'options.*.texte' => 'required_if:type,qcm|string',
            'options.*.is_correct' => 'required_if:type,qcm|boolean',
        ]);

        if ($question->exam_id) {
            $exam = $question->exam;
            
            if ($request->user()->isTrainer() && !$request->user()->hasRole('Directeur') && !in_array((int)$exam->user_id, $request->user()->getAllowedTrainerUserIds(), true)) {
                abort(403, 'Vous ne pouvez pas modifier les questions de cet examen.');
            }

            if ($exam->scheduled_at && $exam->scheduled_at->isPast() && !$request->user()->hasRole('Directeur') && $exam->examResults()->exists()) {
                return redirect()->back()->with('error', 'Impossible de modifier la question car cet examen a déjà commencé et contient des participations.');
            }
            
            $otherPoints = $exam->questions()->where('id', '!=', $question->id)->sum('points');
            
            if (($otherPoints + $validated['points']) > $exam->total_points) {
                return redirect()->back()->with('error', "Mise à jour impossible : le total des points d'examen (" . ($otherPoints + $validated['points']) . ") dépasserait le barème ({$exam->total_points}).");
            }
        } elseif ($question->chapter_id) {
            $chapter = $question->chapter;
            $otherPoints = $chapter->questions()->where('id', '!=', $question->id)->sum('points');
            
            if (($otherPoints + $validated['points']) > $chapter->exercise_points) {
                return redirect()->back()->with('error', "Mise à jour impossible : le total des points d'exercice (" . ($otherPoints + $validated['points']) . ") dépasserait le barème ({$chapter->exercise_points}).");
            }
        }

        if ($question->chapter_id && !$request->user()->hasRole('Directeur')) {
            $question->chapter->update(['is_approved' => false]);
        }

        $question->update($validated);

        if (isset($validated['type'])) {
            if ($validated['type'] === 'qcm' && !empty($validated['options'])) {
                $keepIds = [];
                foreach ($validated['options'] as $optionData) {
                    if (!empty($optionData['id'])) {
                        $option = $question->options()->find($optionData['id']);
                        if ($option) {
                            $option->update($optionData);
                            $keepIds[] = $option->id;
                        }
                    } else {
                        $newOption = $question->options()->create($optionData);
                        $keepIds[] = $newOption->id;
                    }
                }
                $question->options()->whereNotIn('id', $keepIds)->delete();
            } else {
                $question->options()->delete();
            }
        }

        return redirect()->back()->with('success', 'Question mise à jour.');
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        if (request()->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if ($question->exam_id) {
            $exam = $question->exam;
            if (request()->user()->isTrainer() && !request()->user()->hasRole('Directeur') && !in_array((int)$exam->user_id, request()->user()->getAllowedTrainerUserIds(), true)) {
                abort(403, 'Vous ne pouvez pas supprimer les questions de cet examen.');
            }
            if ($exam->scheduled_at && $exam->scheduled_at->isPast() && !request()->user()->hasRole('Directeur') && $exam->examResults()->exists()) {
                return redirect()->back()->with('error', 'Impossible de supprimer la question car cet examen a déjà commencé et contient des participations.');
            }
        }

        if ($question->chapter_id && !request()->user()->hasRole('Directeur')) {
            $question->chapter->update(['is_approved' => false]);
        }

        $question->delete();
        return redirect()->back()->with('success', 'Question supprimée.');
    }
}
