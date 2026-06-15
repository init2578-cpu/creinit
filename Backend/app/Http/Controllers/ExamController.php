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

        // Mark exam-related notifications as read
        $user->unreadNotifications()
             ->where('type', \App\Notifications\ExamResultGradedNotification::class)
             ->update(['read_at' => now()]);

        // Get the student's group IDs
        $groupIds = $user->studentGroups()->pluck('groups.id');

        $exams = Exam::whereHas('groups', function ($query) use ($groupIds) {
                $query->whereIn('groups.id', $groupIds);
            })
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

    public function show(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        if (!$exam->is_practice) {
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existing) {
                if ($existing->status === 'completed') {
                    return redirect()->route('student.exams.index')->with('success', "Vous avez déjà passé cet examen.");
                }
                
                if ($existing->status === 'blocked' || $existing->status === 'started') {
                    if ($existing->status === 'started') {
                        $existing->update(['status' => 'blocked']);
                    }
                    return redirect()->route('student.exams.index')->with('error', "Cet examen a été bloqué suite à une interruption. Veuillez contacter votre formateur pour le débloquer.");
                }
            }
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

    public function start(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$exam->is_approved || (!$exam->can_start && !$exam->is_practice)) {
            return response()->json(['error' => "Cet examen n'est pas accessible actuellement."], 403);
        }

        if (!$exam->is_practice) {
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$existing) {
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'user_id' => $request->user()->id,
                    'status' => 'started',
                    'started_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true]);
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
            $result = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            $finalScore = $totalPoints > 0 ? ($score / $totalPoints) * 20 : 0;

            if ($result) {
                $result->update([
                    'score' => $finalScore,
                    'status' => 'completed',
                    'finished_at' => now(),
                    'answers' => $validated['answers'],
                ]);
            } else {
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'user_id' => $request->user()->id,
                    'score' => $finalScore,
                    'status' => 'completed',
                    'finished_at' => now(),
                    'answers' => $validated['answers'],
                ]);
            }

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

    /**
     * Download the exam document (énoncé).
     */
    public function download(Request $request, Exam $exam): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if (!$exam->is_approved || (!$exam->can_start && !$exam->is_practice)) {
            return redirect()->back()->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        if ($exam->is_online || !$exam->document_path) {
            return redirect()->back()->with('error', "Aucun énoncé disponible pour cet examen.");
        }

        $filePath = storage_path('app/public/' . $exam->document_path);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', "Le fichier n'existe pas.");
        }

        return response()->download($filePath);
    }
}
