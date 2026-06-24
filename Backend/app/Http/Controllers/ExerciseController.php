<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\ExerciseSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    /**
     * List exercises for the current student.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Clear unread notifications about graded exercises when visiting this page
        $user->unreadNotifications()->where('type', \App\Notifications\ExerciseGradedNotification::class)->update(['read_at' => now()]);
        
        $moduleIds = $user->studentGroups()->pluck('module_id');

        $chapters = Chapter::whereIn('module_id', $moduleIds)
            ->whereIn('exercise_type', ['online', 'file'])
            ->where('is_published', true)
            ->where('is_approved', true)
            ->with(['module', 'questions'])
            ->get()
            ->map(function ($chapter) use ($user) {
                $submission = ExerciseSubmission::where('chapter_id', $chapter->id)
                    ->where('user_id', $user->id)
                    ->first();
                $chapter->my_submission = $submission;
                return $chapter;
            });

        return Inertia::render('Student/Exercises', [
            'exercises' => $chapters,
        ]);
    }

    /**
     * Show the online exercise (quiz) page for a student.
     */
    public function showOnline(Request $request, Chapter $chapter): Response
    {
        if (!$chapter->is_published || (!$chapter->is_approved && !$request->user()->hasRole('Directeur') && !$request->user()->isTrainer())) {
            abort(403, 'Cet exercice n\'est pas encore publié ou validé.');
        }

        $chapter->load(['module', 'questions.options']);
        return Inertia::render('Student/TakeExercise', [
            'exercise' => $chapter,
            'is_practice' => $request->query('practice') == '1',
        ]);
    }

    /**
     * Submit an exercise for a specific chapter.
     * Handles both file uploads and online (JSON answers) submissions.
     */
    public function submit(Request $request, Chapter $chapter): RedirectResponse
    {
        if (!$chapter->is_published || (!$chapter->is_approved && !Auth::user()->hasRole('Directeur') && !Auth::user()->isTrainer())) {
            abort(403, 'Cet exercice n\'est pas encore publié ou validé.');
        }

        $user = Auth::user();
        $type = $request->input('type');

        if ($type === 'online') {
            $request->validate([
                'answers' => 'required|array',
            ]);

            $answers = $request->input('answers');
            $chapter->load('questions.options');
            
            $autoGrade = 0;
            $hasOpenQuestions = false;

            foreach ($chapter->questions as $question) {
                if ($question->type === 'qcm') {
                    $submittedOptionId = $answers[$question->id] ?? null;
                    $correctOption = $question->options->where('is_correct', true)->first();
                    
                    if ($submittedOptionId && $correctOption && (int)$submittedOptionId === $correctOption->id) {
                        $autoGrade += $question->points;
                    }
                } else {
                    $hasOpenQuestions = true;
                }
            }

            if ($request->boolean('is_practice')) {
                // Find existing submission ID to redirect to
                $existingSubmission = ExerciseSubmission::where('user_id', $user->id)
                    ->where('chapter_id', $chapter->id)
                    ->first();
                
                return redirect()->route('student.exercises.result', $existingSubmission->id)
                    ->with([
                        'practice_answers' => $answers,
                        'practice_grade' => $autoGrade,
                        'success' => 'Entraînement terminé. Voici votre correction automatique.'
                    ]);
            }

            $submission = ExerciseSubmission::updateOrCreate(
                ['user_id' => $user->id, 'chapter_id' => $chapter->id],
                [
                    'answers' => $answers,
                    'student_comment' => null,
                    'file_path' => null,
                    'status' => 'pending',
                    'grade' => $autoGrade,
                    'trainer_feedback' => null,
                ]
            );

            $this->notifyTrainer($submission);

            return back()->with('success', 'Vos réponses ont été soumises. Score QCM calculé : ' . $autoGrade . ' points.');
        }

        // File submission
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,zip,rar,doc,docx,jpg,png',
            'student_comment' => 'nullable|string|max:500',
        ]);

        $path = $request->file('file')->store('exercises/' . $chapter->id, 'public');

        $submission = ExerciseSubmission::updateOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            [
                'file_path' => $path,
                'student_comment' => $request->student_comment,
                'status' => 'pending',
                'grade' => null,
                'trainer_feedback' => null,
            ]
        );

        $this->notifyTrainer($submission);

        return back()->with('success', 'Votre exercice a été soumis avec succès.');
    }

    /**
     * Notify the trainer of the student's group about a new submission.
     */
    protected function notifyTrainer(ExerciseSubmission $submission): void
    {
        $user = $submission->user;
        $chapter = $submission->chapter;
        
        // Find the group of this student for this module
        $group = $user->studentGroups()
            ->where('module_id', $chapter->module_id)
            ->with('formateur')
            ->first();

        if ($group && $group->formateur) {
            $group->formateur->notify(new \App\Notifications\NewExerciseSubmissionNotification($submission));
        }
    }

    /**
     * Trainer grades a submission.
     */
    public function grade(Request $request, ExerciseSubmission $submission): RedirectResponse
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:20',
            'trainer_feedback' => 'nullable|string|max:1000',
        ]);

        $submission->update([
            'grade' => $request->grade,
            'trainer_feedback' => $request->trainer_feedback,
            'status' => 'graded',
        ]);

        // Notify student
        $submission->user->notify(new \App\Notifications\ExerciseGradedNotification($submission));

        return back()->with('success', 'La note a été enregistrée.');
    }

    /**
     * Show exercise results for a student.
     */
    public function showResult(ExerciseSubmission $submission): Response
    {
        // Add auth check
        if (Auth::id() !== $submission->user_id) {
            abort(403);
        }

        $submission->load(['chapter.module', 'chapter.questions.options']);

        if (session()->has('practice_answers')) {
            $submission->answers = session('practice_answers');
            $submission->grade = session('practice_grade');
            $submission->trainer_feedback = "Ceci est le résultat de votre session d'entraînement. Votre note officielle n'a pas été modifiée.";
            $is_practice = true;
        } else {
            $is_practice = false;
        }

        return Inertia::render('Student/ExerciseResult', [
            'submission' => $submission,
            'is_practice' => $is_practice,
        ]);
    }

    /**
     * Download submission file (for trainer).
     */
    public function download(ExerciseSubmission $submission)
    {
        // Simple security: Trainer or owner?
        if (!Auth::user()->isTrainer() && !Auth::user()->hasRole('Directeur') && Auth::id() !== $submission->user_id) {
            abort(403);
        }

        return Storage::disk('private')->download($submission->file_path);
    }
}
