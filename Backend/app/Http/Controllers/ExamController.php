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

    /**
     * Helper to compute a score out of 20 from exam questions and student answers.
     */
    public function calculateScore(Exam $exam, array $answers): float
    {
        $score = 0;
        $totalPoints = 0;
        $exam->loadMissing('questions.options');

        foreach ($exam->questions as $question) {
            $totalPoints += (float)$question->points;

            if ($question->type === 'qcm') {
                $correctOptions = $question->options->where('is_correct', true);
                $correctOptionIds = $correctOptions->pluck('id')->toArray();

                $userAnswers = $answers[$question->id] ?? [];
                if (!is_array($userAnswers)) {
                    $userAnswers = !empty($userAnswers) ? [$userAnswers] : [];
                }

                $totalCorrectExpected = count($correctOptionIds);
                $questionPoints = (float)$question->points;
                
                $pointsPerCorrectOption = $totalCorrectExpected > 0 ? $questionPoints / $totalCorrectExpected : 0;
                
                $questionScore = 0;
                foreach ($userAnswers as $ansId) {
                    if (in_array((int)$ansId, $correctOptionIds, true)) {
                        $questionScore += $pointsPerCorrectOption;
                    } else {
                        $questionScore -= $pointsPerCorrectOption;
                    }
                }
                
                if ($questionScore < 0) {
                    $questionScore = 0;
                }
                if ($questionScore > $questionPoints) {
                    $questionScore = $questionPoints;
                }
                
                $score += $questionScore;
            } else {
                $openScores = $answers['_question_scores'] ?? [];
                if (isset($openScores[(string)$question->id])) {
                    $score += min((float)$openScores[(string)$question->id], (float)$question->points);
                }
            }
        }

        return $totalPoints > 0 ? round(($score / $totalPoints) * 20, 2) : 0.0;
    }

    public function show(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        $savedAnswers = [];

        if (!$exam->is_practice) {
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existing) {
                if ($existing->status === 'completed') {
                    return redirect()->route('student.exams.index')->with('success', "Vous avez déjà passé cet examen.");
                }
                
                if ($existing->status === 'blocked') {
                    return redirect()->route('student.exams.index')->with('error', "Cet examen a été bloqué suite à une interruption. Veuillez contacter votre formateur pour le débloquer.");
                }

                if ($existing->answers && is_array($existing->answers)) {
                    $savedAnswers = $existing->answers;
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
            'savedAnswers' => $savedAnswers,
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
     * Auto-save draft answers continuously during the exam.
     */
    public function saveAnswers(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$exam->is_approved) {
            return response()->json(['error' => "Cet examen n'est pas accessible actuellement."], 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $user = $request->user();
        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        $score = $this->calculateScore($exam, $validated['answers']);

        if (!$result) {
            $result = ExamResult::create([
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'score' => $score,
                'status' => 'started',
                'started_at' => now(),
                'answers' => $validated['answers'],
            ]);
        } else if ($result->status !== 'completed') {
            $result->update([
                'score' => $score,
                'answers' => $validated['answers'],
            ]);
        }

        return response()->json(['success' => true, 'score' => $result->score]);
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

        $score = 0;
        $totalPoints = 0;
        $feedback = [];

        $exam->loadMissing(['questions.options']);

        foreach ($exam->questions as $question) {
            $totalPoints += (float)$question->points;

            if ($question->type === 'qcm') {
                $correctOptions = $question->options()->where('is_correct', true)->get();
                $correctOptionIds = $correctOptions->pluck('id')->toArray();

                $userAnswers = $validated['answers'][$question->id] ?? [];
                if (!is_array($userAnswers)) {
                    $userAnswers = !empty($userAnswers) ? [$userAnswers] : [];
                }

                $totalCorrectExpected = count($correctOptionIds);
                $questionPoints = (float)$question->points;
                
                $pointsPerCorrectOption = $totalCorrectExpected > 0 ? $questionPoints / $totalCorrectExpected : 0;
                
                $questionScore = 0;
                foreach ($userAnswers as $ansId) {
                    if (in_array((int)$ansId, $correctOptionIds, true)) {
                        $questionScore += $pointsPerCorrectOption;
                    } else {
                        $questionScore -= $pointsPerCorrectOption;
                    }
                }
                
                if ($questionScore < 0) {
                    $questionScore = 0;
                }
                if ($questionScore > $questionPoints) {
                    $questionScore = $questionPoints;
                }
                
                $score += $questionScore;

                if ($exam->is_practice) {
                    $isCorrect = ($questionScore == $questionPoints && $questionPoints > 0);
                    $feedback[] = [
                        'question_id' => $question->id,
                        'is_correct' => $isCorrect,
                        'correct_options' => $correctOptions->toArray(),
                        'explanation' => $isCorrect ? 'Correct !' : ($questionScore > 0 ? 'Partiellement correct.' : 'Incorrect.'),
                    ];
                }
            } else {
                if ($exam->is_practice) {
                    $feedback[] = [
                        'question_id' => $question->id,
                        'is_correct' => null,
                        'correct_option_id' => null,
                        'explanation' => 'Réponse attendue : ' . ($question->expected_answer ?? 'Non spécifiée'),
                    ];
                }
            }
        }

        if (!$exam->is_practice) {
            $result = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            $finalScore = $totalPoints > 0 ? round(($score / $totalPoints) * 20, 2) : 0;

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

            if ($exam->isExpired()) {
                return redirect()->route('student.dashboard')->with('info', "Le temps imparti était écoulé. Vos réponses enregistrées ont été soumises.");
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

    /**
     * Display the detailed correction for an exam after grades are published.
     */
    public function result(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->are_grades_published) {
            return redirect()->route('student.dashboard')->with('error', "La correction de cet examen n'est pas encore disponible.");
        }

        $exam->load(['module', 'questions.options']);

        $user = $request->user();
        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$result) {
            return redirect()->route('student.dashboard')->with('error', "Vous n'avez pas de résultat pour cet examen.");
        }

        return Inertia::render('Student/ExamResult', [
            'exam' => $exam,
            'result' => $result,
        ]);
    }
}
