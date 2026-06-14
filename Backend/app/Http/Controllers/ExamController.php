<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Option;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    /**
     * List exams for the current student.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get the student's groups and their module IDs
        $moduleIds = $user->studentGroups()->pluck('module_id');

        $exams = Exam::whereIn('module_id', $moduleIds)
            ->where('is_approved', true)
            ->with(['module', 'questions.options'])
            ->get()
            ->map(function ($exam) use ($user) {
                // Ensure questions are sequential for list previews if used
                $exam->setRelation('questions', $exam->questions->values());

                $result = ExamResult::where('exam_id', $exam->id)
                    ->where('user_id', $user->id)
                    ->first();
                $exam->my_result = $result;
                return $exam;
            });

        return Inertia::render('Student/Exams', [
            'exams' => $exams,
        ]);
    }

    public function show(Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        $exam->load(['questions.options']);

        // Ensure questions stay as a sequential array even if custom ordered
        $exam->setRelation('questions', $exam->questions->values());

        $component = $exam->is_practice ? 'Student/PracticeExam' : 'LMS/TakeExam';

        if (!$exam->can_start && !$exam->is_practice) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        return Inertia::render($component, [
            'exam' => $exam,
        ]);
    }

    /**
     * Submit exam/practice results.
     */
    public function submit(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        if (!$exam->is_practice && $exam->isExpired()) {
            // We still save the result but maybe we should flag it or just block it.
            // For now, let's block it if it's way over the limit, otherwise allow but it's risky.
            // Requirement said "rejeter si isExpired".
            return redirect()->route('student.dashboard')->with('error', "Délai expiré. Votre examen n'a pas pu être validé.");
        }

        $score = 0;
        $totalPoints = 0;
        $feedback = [];

        foreach ($exam->questions as $question) {
            $totalPoints += $question->points;
            $userAnswerId = $validated['answers'][$question->id] ?? null;

            $correctOption = $question->options()->where('is_correct', true)->first();
            $isCorrect = ($userAnswerId == $correctOption?->id);

            if ($isCorrect) {
                $score += $question->points;
            }

            // Correction immédiate pour le mode practice
            if ($exam->is_practice) {
                $feedback[] = [
                    'question_id' => $question->id,
                    'is_correct' => $isCorrect,
                    'correct_option_id' => $correctOption?->id,
                    'explanation' => $isCorrect ? 'Correct !' : 'Incorrect.',
                ];
            }
        }

        if (!$exam->is_practice) {
            ExamResult::create([
                'exam_id' => $exam->id,
                'user_id' => $request->user()->id,
                'score' => $totalPoints > 0 ? ($score / $totalPoints) * 20 : 0, // Ramenant sur 20
                'finished_at' => now(),
                'answers' => $validated['answers'],
            ]);

            return redirect()->route('student.dashboard')->with('success', 'Examen terminé.');
        }

        // Mode Practice : Retourner le feedback immédiat
        return Inertia::render('LMS/PracticeResult', [
            'score' => $score,
            'total' => $totalPoints,
            'feedback' => $feedback,
            'exam' => $exam,
        ]);
    }
}
