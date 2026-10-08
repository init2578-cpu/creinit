<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Option;
use App\Models\Group;
use App\Models\User;
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

        $exams = Exam::where(function ($query) use ($groupIds, $user) {
                if ($groupIds->isNotEmpty()) {
                    $query->whereHas('groups', function ($q) use ($groupIds) {
                        $q->whereIn('groups.id', $groupIds);
                    });
                } else {
                    $query->whereRaw('0 = 1');
                }
                $query->orWhereHas('particularStudents', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })
            ->where('is_approved', true)
            ->with(['module', 'rattrapages.users'])
            ->get()
            ->map(function ($exam) use ($user) {
                $result = ExamResult::where('exam_id', $exam->id)
                    ->where('user_id', $user->id)
                    ->first();
                $exam->my_result = $result;

                // Check if user has an assigned rattrapage session
                $rattrapage = $exam->getUpcomingOrActiveRattrapageForUser($user);
                if ($rattrapage) {
                    $rattrapageUserPivot = $rattrapage->users->firstWhere('id', $user->id)?->pivot;
                    $pivotStatus = $rattrapageUserPivot?->status ?? 'scheduled';
                    $exam->rattrapage_session = [
                        'id' => $rattrapage->id,
                        'titre' => $rattrapage->titre,
                        'scheduled_at' => $rattrapage->scheduled_at ? $rattrapage->scheduled_at->toISOString() : null,
                        'scheduled_at_formatted' => $rattrapage->scheduled_at ? $rattrapage->scheduled_at->format('d/m/Y à H:i') : null,
                        'duree_minutes' => $rattrapage->duree_minutes,
                        'end_at' => $rattrapage->end_at ? $rattrapage->end_at->toISOString() : null,
                        'end_at_formatted' => $rattrapage->end_at ? $rattrapage->end_at->format('H:i') : null,
                        'can_start' => $rattrapage->can_start,
                        'has_ended' => $rattrapage->has_ended,
                        'pivot_status' => $pivotStatus,
                    ];

                    $isRattrapageCompleted = ($pivotStatus === 'completed')
                        || ($result && $result->is_rattrapage && $result->exam_rattrapage_id === $rattrapage->id && $result->status === 'completed');

                    if ($rattrapage->can_start && !$isRattrapageCompleted) {
                        $exam->can_start = true;
                        $exam->has_ended = false;
                    } elseif (!$rattrapage->has_ended && !$isRattrapageCompleted) {
                        $exam->has_ended = false;
                    }
                } else {
                    $exam->rattrapage_session = null;
                }

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

    /**
     * Check if a student is authorized to access an exam (via assigned group or direct particular assignment).
     */
    public function canStudentAccessExam(User $user, Exam $exam): bool
    {
        $inGroup = $exam->groups()->whereHas('students', fn($q) => $q->where('users.id', $user->id))->exists();
        if ($inGroup) {
            return true;
        }

        return $exam->particularStudents()->where('users.id', $user->id)->exists();
    }

    public function show(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved || !$this->canStudentAccessExam($request->user(), $exam)) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        $savedAnswers = [];
        $existingResult = null;
        $activeRattrapage = $exam->getActiveRattrapageForUser($request->user());

        if (!$exam->is_practice) {
            $existingResult = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingResult) {
                if ($activeRattrapage !== null) {
                    if ($existingResult->is_rattrapage && $existingResult->exam_rattrapage_id === $activeRattrapage->id) {
                        if ($existingResult->status === 'completed') {
                            return redirect()->route('student.exams.index')->with('success', "Vous avez déjà passé cet examen de rattrapage.");
                        }
                        if ($existingResult->status === 'blocked') {
                            return redirect()->route('student.exams.index')->with('error', "Cet examen a été bloqué suite à une interruption. Veuillez contacter votre formateur pour le débloquer.");
                        }
                        if ($existingResult->answers && is_array($existingResult->answers)) {
                            $savedAnswers = $existingResult->answers;
                        }
                    } else {
                        // User has an existing result from regular session, but is starting a new rattrapage
                        $savedAnswers = [];
                    }
                } else {
                    if ($existingResult->status === 'completed') {
                        return redirect()->route('student.exams.index')->with('success', "Vous avez déjà passé cet examen.");
                    }
                    
                    if ($existingResult->status === 'blocked') {
                        return redirect()->route('student.exams.index')->with('error', "Cet examen a été bloqué suite à une interruption. Veuillez contacter votre formateur pour le débloquer.");
                    }

                    if ($existingResult->answers && is_array($existingResult->answers)) {
                        $savedAnswers = $existingResult->answers;
                    }
                }
            }
        }

        $component = $exam->is_practice ? 'Student/PracticeExam' : 'LMS/TakeExam';

        // Allow access if regular session can start, active rattrapage is available, or resuming in-progress attempt
        $canAccess = $exam->can_start 
            || ($activeRattrapage !== null)
            || ($existingResult && $existingResult->status === 'started' && !$exam->has_ended);

        if (!$canAccess && !$exam->is_practice) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible actuellement.");
        }

        // Adjust timing for active rattrapage so LMS/TakeExam Timer reflects the make-up session
        if ($activeRattrapage !== null) {
            $exam->scheduled_at = $activeRattrapage->scheduled_at;
            $exam->duree_minutes = $activeRattrapage->duree_minutes;
        }

        $exam->load(['questions.options']);
        $exam->setRelation('questions', $exam->questions->values());

        if (!$exam->is_practice) {
            $exam->questions->each(function ($question) {
                $question->makeHidden('expected_answer');
                $question->options->each->makeHidden('is_correct');
            });
        }

        return Inertia::render($component, [
            'exam' => $exam,
            'savedAnswers' => $savedAnswers,
        ]);
    }

    public function start(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$this->canStudentAccessExam($request->user(), $exam)) {
            return response()->json(['error' => "Cet examen n'est pas accessible actuellement."], 403);
        }

        if (!$exam->is_practice) {
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $request->user()->id)
                ->first();

            $activeRattrapage = $exam->getActiveRattrapageForUser($request->user());
            // Allow access if regular session can start, active rattrapage is available, or resuming started attempt
            $canAccess = $exam->is_approved && (
                $exam->can_start 
                || ($activeRattrapage !== null)
                || ($existing && $existing->status === 'started' && !$exam->has_ended)
            );

            if (!$canAccess) {
                return response()->json(['error' => "Cet examen n'est pas accessible actuellement."], 403);
            }

            if (!$existing) {
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'user_id' => $request->user()->id,
                    'status' => 'started',
                    'started_at' => now(),
                    'is_rattrapage' => $activeRattrapage !== null,
                    'exam_rattrapage_id' => $activeRattrapage?->id,
                ]);
            } else if ($activeRattrapage !== null) {
                if ($existing->exam_rattrapage_id !== $activeRattrapage->id || $existing->status !== 'started') {
                    $existing->update([
                        'status' => 'started',
                        'is_rattrapage' => true,
                        'exam_rattrapage_id' => $activeRattrapage->id,
                        'started_at' => now(),
                        'finished_at' => null,
                        'answers' => null,
                    ]);
                }
            }
        } else {
            if (!$exam->is_approved) {
                return response()->json(['error' => "Cet examen n'est pas accessible actuellement."], 403);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Auto-save draft answers continuously during the exam.
     */
    public function saveAnswers(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$exam->is_approved || !$this->canStudentAccessExam($request->user(), $exam)) {
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
        $activeRattrapage = $exam->getActiveRattrapageForUser($user);

        if (!$result) {
            $result = ExamResult::create([
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'score' => $score,
                'status' => 'started',
                'started_at' => now(),
                'answers' => $validated['answers'],
                'is_rattrapage' => $activeRattrapage !== null,
                'exam_rattrapage_id' => $activeRattrapage?->id,
            ]);
        } else if ($result->status !== 'completed' || ($activeRattrapage !== null && ($result->exam_rattrapage_id !== $activeRattrapage->id || !$result->is_rattrapage))) {
            $updateData = [
                'score' => $score,
                'answers' => $validated['answers'],
                'status' => 'started',
            ];
            if ($activeRattrapage !== null) {
                $updateData['is_rattrapage'] = true;
                $updateData['exam_rattrapage_id'] = $activeRattrapage->id;
            }
            $result->update($updateData);
        }

        return response()->json(['success' => true, 'score' => $result->score]);
    }

    /**
     * Submit exam/practice results.
     */
    public function submit(Request $request, Exam $exam): Response|RedirectResponse
    {
        if (!$exam->is_approved || !$this->canStudentAccessExam($request->user(), $exam)) {
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
            $user = $request->user();
            $result = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->first();

            $activeRattrapage = $exam->getActiveRattrapageForUser($user);

            $finalScore = $totalPoints > 0 ? round(($score / $totalPoints) * 20, 2) : 0;
            $isBlocked = $request->boolean('is_blocked');
            $status = $isBlocked ? 'blocked' : 'completed';

            $resultData = [
                'score' => $finalScore,
                'status' => $status,
                'finished_at' => now(),
                'answers' => $validated['answers'],
            ];

            if ($activeRattrapage !== null) {
                $resultData['is_rattrapage'] = true;
                $resultData['exam_rattrapage_id'] = $activeRattrapage->id;
            }

            if ($result) {
                $result->update($resultData);
            } else {
                $result = ExamResult::create(array_merge($resultData, [
                    'exam_id' => $exam->id,
                    'user_id' => $user->id,
                ]));
            }

            if ($activeRattrapage !== null && !$isBlocked) {
                $activeRattrapage->users()->updateExistingPivot($user->id, [
                    'status' => 'completed',
                ]);
            }

            if ($isBlocked) {
                return redirect()->route('student.dashboard')->with('error', "Examen verrouillé suite à une infraction aux consignes (changement de fenêtre ou sortie du mode plein écran). Vos réponses ont été enregistrées et transmises à votre formateur.");
            }

            if ($exam->isExpired() && $activeRattrapage === null) {
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
        $activeRattrapage = $exam->getActiveRattrapageForUser($request->user());
        $canAccess = $exam->can_start || ($activeRattrapage !== null);

        if (!$exam->is_approved || !$this->canStudentAccessExam($request->user(), $exam) || (!$canAccess && !$exam->is_practice)) {
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
        if (!$this->canStudentAccessExam($request->user(), $exam)) {
            return redirect()->route('student.exams.index')->with('error', "Cet examen n'est pas accessible.");
        }

        if (!$exam->are_grades_published) {
            return redirect()->route('student.dashboard')->with('error', "La correction de cet examen n'est pas encore disponible.");
        }

        $user = $request->user();
        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$result || $result->status !== 'completed' || $result->score === null) {
            return redirect()->route('student.exams.index')->with('error', "Vous n'avez pas passé cet examen. Vous ne pouvez pas accéder aux questions ni à la correction.");
        }

        $exam->load(['module', 'questions.options']);

        return Inertia::render('Student/ExamResult', [
            'exam' => $exam,
            'result' => $result,
        ]);
    }
}
