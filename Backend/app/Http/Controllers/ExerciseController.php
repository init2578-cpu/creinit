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
        $moduleIds = $user->studentGroups()->pluck('module_id');

        $chapters = Chapter::whereIn('module_id', $moduleIds)
            ->whereIn('exercise_type', ['online', 'file'])
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
    public function showOnline(Chapter $chapter): Response
    {
        $chapter->load(['module', 'questions.options']);
        return Inertia::render('Student/TakeExercise', [
            'exercise' => $chapter,
        ]);
    }

    /**
     * Submit an exercise for a specific chapter.
     * Handles both file uploads and online (JSON answers) submissions.
     */
    public function submit(Request $request, Chapter $chapter): RedirectResponse
    {
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
        if (Auth::id() !== $submission->user_id) {
            abort(403);
        }

        $submission->load(['chapter.questions.options', 'chapter.module']);

        return Inertia::render('Student/ExerciseResult', [
            'submission' => $submission,
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
