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

            $exerciseQuery->whereIn('module_id', $moduleIds)
                ->with(['exerciseSubmissions' => function($q) use ($studentIds) {
                    $q->whereIn('user_id', $studentIds)->with('user.studentGroups');
                }]);

            $moduleQuery->whereIn('id', $moduleIds);
        } else {
            $exerciseQuery->with('exerciseSubmissions.user');
        }

        return Inertia::render('Scolarite/ExercisesIndex', [
            'exercises' => $exerciseQuery->get(),
            'modules'   => $moduleQuery->get(),
        ]);
    }

    /**
     * Update exercise settings for a chapter.
     */
    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
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

    public function gradeSubmission(Request $request, ExerciseSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:' . ($submission->chapter->exercise_points ?? 20),
            'trainer_feedback' => 'nullable|string',
            'status' => 'required|in:graded,rejected',
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
        $validated = $request->validate([
            'enonce' => 'required|string',
            'points' => 'required|numeric|min:0',
            'type' => 'required|in:qcm,open',
            'options' => 'array',
            'options.*.texte' => 'required_if:type,qcm|string',
            'options.*.is_correct' => 'required_if:type,qcm|boolean',
        ]);

        $currentPoints = $chapter->questions()->sum('points');
        if (($currentPoints + $validated['points']) > $chapter->exercise_points) {
            return redirect()->back()->with('error', "Le total des points des questions (" . ($currentPoints + $validated['points']) . ") ne peut pas dépasser le barème de l'exercice ({$chapter->exercise_points}).");
        }

        $question = $chapter->questions()->create([
            'enonce' => $validated['enonce'],
            'points' => $validated['points'],
            'type' => $validated['type'],
            'ordre' => $chapter->questions()->count() + 1,
        ]);

        if ($validated['type'] === 'qcm' && !empty($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $question->options()->create($optionData);
            }
        }

        return redirect()->back()->with('success', 'Question ajoutée.');
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'points' => 'required|numeric|min:0',
            'enonce' => 'sometimes|required|string',
        ]);

        if ($question->exam_id) {
            $exam = $question->exam;
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

        $question->update($validated);

        return redirect()->back()->with('success', 'Question mise à jour.');
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        $question->delete();
        return redirect()->back()->with('success', 'Question supprimée.');
    }
}
