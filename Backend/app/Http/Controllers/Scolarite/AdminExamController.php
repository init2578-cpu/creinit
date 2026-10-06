<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Module;
use App\Models\Group;
use App\Models\User;
use App\Models\ExamRattrapage;
use App\Models\Attendance;
use App\Notifications\NewExamAvailableNotification;
use App\Notifications\ExamAssignedNotification;
use App\Notifications\NewExamRattrapageNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminExamController extends Controller
{
    /**
     * Determine if a user can view an exam.
     */
    private function canViewExam(User $user, Exam $exam): bool
    {
        if ($exam->is_exclusive_directeur || ($exam->groups()->count() === 0 && $exam->particularStudents()->exists())) {
            return $user->hasRole('Directeur');
        }

        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            return true;
        }

        $allowedUserIds = $user->getAllowedTrainerUserIds();
        if (in_array((int)$exam->user_id, $allowedUserIds, true)) {
            return true;
        }

        return $exam->groups()->whereIn('formateur_id', $allowedUserIds)->exists();
    }

    /**
     * Determine if a user can modify or manage an exam.
     * Assistants (Stagiaires) can view exams proposed by their primary trainer,
     * but CANNOT modify, update, delete, duplicate, or manage questions/grades unless they created it themselves.
     */
    private function canModifyExam(User $user, Exam $exam): bool
    {
        if ($exam->is_exclusive_directeur || ($exam->groups()->count() === 0 && $exam->particularStudents()->exists())) {
            return $user->hasRole('Directeur');
        }

        if ($user->hasRole('Directeur')) {
            return true;
        }

        if ($user->hasRole('Secrétaire')) {
            return false;
        }

        $isAssistant = $user->hasRole('Stagiaire') || !empty($user->internshipRecord?->tuteur_id);

        if ($isAssistant) {
            // An assistant can ONLY modify an exam if they are the direct creator
            return (int)$exam->user_id === (int)$user->id;
        }

        $allowedUserIds = $user->getAllowedTrainerUserIds();
        if (in_array((int)$exam->user_id, $allowedUserIds, true)) {
            return true;
        }

        return $exam->groups()->whereIn('formateur_id', $allowedUserIds)->exists();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole('Secrétaire')) {
            $examQuery = Exam::with(['module', 'user', 'questions.options', 'groups', 'rattrapages.users', 'particularStudents'])->orderBy('created_at', 'desc');
        } else {
            $examQuery = Exam::with(['module', 'user', 'questions.options', 'examResults.user', 'groups', 'rattrapages.users', 'particularStudents'])->orderBy('created_at', 'desc');
        }
        $moduleQuery = Module::query();
        $groupsQuery = Group::query();

        // Non-directors MUST NOT see exclusive director exams or particular student exams
        if (!$user->hasRole('Directeur')) {
            $examQuery->where('is_exclusive_directeur', false)
                      ->whereDoesntHave('particularStudents');
        }

        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            $allowedUserIds = $user->getAllowedTrainerUserIds();
            $examQuery->where(function ($query) use ($allowedUserIds) {
                $query->whereIn('user_id', $allowedUserIds)
                      ->orWhereHas('groups', function ($gQuery) use ($allowedUserIds) {
                          $gQuery->whereIn('formateur_id', $allowedUserIds);
                      });
            });
            $moduleIds = Group::whereIn('formateur_id', $allowedUserIds)->pluck('module_id');
            if ($moduleIds->isNotEmpty()) {
                $moduleQuery->whereIn('id', $moduleIds);
            } else {
                $moduleQuery->whereRaw('1 = 0');
            }
            $groupsQuery->whereIn('formateur_id', $allowedUserIds);
        }

        $isDirecteur = $user->hasRole('Directeur');

        $trainers = [];
        $particularStudents = [];
        if ($isDirecteur) {
            $trainers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Formateur', 'Stagiaire']);
            })->get(['id', 'name'])->filter(fn($u) => $u->isTrainer())->values();

            $particularStudents = User::role('Apprenant')
                ->where('is_particulier', true)
                ->where('is_active', true)
                ->with('particularModules')
                ->orderBy('name')
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'telephone' => $u->telephone,
                    'module_ids' => $u->particularModules->pluck('id')->values(),
                ]);
        }

        return Inertia::render('Scolarite/ExamsIndex', [
            'exams'   => $examQuery->get()->map(function ($exam) use ($isDirecteur, $user) {
                $exam->expected_results_count = User::role('Apprenant')
                    ->where(function ($q) use ($exam, $isDirecteur) {
                        $q->whereHas('studentGroups', function ($query) use ($exam) {
                            $query->whereIn('groups.id', $exam->groups->pluck('id'));
                        });
                        if ($isDirecteur) {
                            $q->orWhereHas('particularExams', function ($query) use ($exam) {
                                $query->where('exams.id', $exam->id);
                            });
                        }
                    })->count();

                $exam->particular_students_count = $exam->particularStudents->count();
                $exam->particular_student_ids = $exam->particularStudents->pluck('id')->values();

                $exam->can_manage = $this->canModifyExam($user, $exam);
                $exam->rattrapages_count = $exam->rattrapages->count();
                $isStarted = $exam->scheduled_at && $exam->scheduled_at->isPast() && $exam->examResults()->exists();
                $exam->can_modify = $isDirecteur || ($exam->can_manage && !$isStarted);
                $exam->can_view_questions = $this->canViewExam($user, $exam);

                return $exam;
            }),
            'modules' => $moduleQuery->get(),
            'groups'  => $groupsQuery->get(),
            'trainers' => $trainers,
            'particular_students' => $particularStudents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'titre' => 'required|string|max:255',
            'type' => 'required|in:online,paper',
            'description' => 'nullable|string',
            'duree_minutes' => 'required|integer|min:1',
            'total_points' => 'required|numeric|min:0',
            'scheduled_at' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
            'particular_student_ids' => 'nullable|array',
            'particular_student_ids.*' => 'exists:users,id',
            'is_exclusive_directeur' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('exams', 'public');
            $validated['document_path'] = $path;
        }

        $user = $request->user();
        $isDirecteur = $user->hasRole('Directeur');
        $validated['is_approved'] = $isDirecteur;

        if ($isDirecteur && $request->filled('user_id')) {
            $validated['user_id'] = $request->input('user_id');
        } else {
            $validated['user_id'] = $user->id;
        }

        $particularStudentIds = $isDirecteur ? ($request->input('particular_student_ids') ?? []) : [];
        $groupIds = $request->input('group_ids', []);

        $isExclusive = false;
        if ($isDirecteur) {
            $isExclusive = $request->boolean('is_exclusive_directeur') 
                || (!empty($particularStudentIds) && empty($groupIds));
        }
        $validated['is_exclusive_directeur'] = $isExclusive;

        $exam = Exam::create($validated);
        $exam->load('module');

        if ($isDirecteur && (int)$exam->user_id !== (int)$user->id && $exam->user) {
            try {
                $exam->user->notify(new ExamAssignedNotification($exam));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erreur d\'envoi de la notification d\'attribution d\'examen: ' . $e->getMessage());
            }
        }

        if ($isDirecteur) {
            $exam->groups()->sync($groupIds);
            $exam->particularStudents()->sync($particularStudentIds);
        } elseif ($user->isTrainer()) {
            $allowedUserIds = $user->getAllowedTrainerUserIds();
            $trainerGroupIds = \App\Models\Group::whereIn('formateur_id', $allowedUserIds)->pluck('id')->toArray();
            $currentGroupIds = $exam->groups()->pluck('groups.id')->toArray();
            $otherGroupIds = array_diff($currentGroupIds, $trainerGroupIds);
            $newTrainerGroupIds = array_intersect($groupIds, $trainerGroupIds);
            $exam->groups()->sync(array_merge($otherGroupIds, $newTrainerGroupIds));
        }

        if ($exam->is_approved) {
            // Notify students enrolled in the assigned groups or directly assigned
            $students = User::role('Apprenant')
                ->where(function ($query) use ($exam) {
                    $query->whereHas('studentGroups', function ($gQuery) use ($exam) {
                        $gQuery->whereIn('groups.id', $exam->groups->pluck('id'));
                    })
                    ->orWhereHas('particularExams', function ($eQuery) use ($exam) {
                        $eQuery->where('exams.id', $exam->id);
                    });
                })->get();

            foreach ($students as $student) {
                $student->notify(new NewExamAvailableNotification($exam));
            }
            $message = 'Examen créé avec succès.';
        } else {
            // Notify Directeur that a new exam proposal is pending validation
            $directors = User::role('Directeur')->get();
            foreach ($directors as $director) {
                $director->notify(new \App\Notifications\ExamPendingValidationNotification($exam, $user));
            }
            $message = 'Examen proposé avec succès. En attente de validation par la Direction.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas modifier cet examen.');
        }

        if ($exam->scheduled_at && $exam->scheduled_at->isPast() && !$request->user()->hasRole('Directeur') && $exam->examResults()->exists()) {
            return redirect()->back()->with('error', 'Impossible de modifier cet examen car il a déjà commencé et contient des participations.');
        }

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'titre' => 'required|string|max:255',
            'type' => 'required|in:online,paper',
            'description' => 'nullable|string',
            'duree_minutes' => 'required|integer|min:1',
            'total_points' => 'required|numeric|min:0',
            'scheduled_at' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
            'particular_student_ids' => 'nullable|array',
            'particular_student_ids.*' => 'exists:users,id',
            'is_exclusive_directeur' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('document')) {
            if ($exam->document_path) {
                Storage::disk('public')->delete($exam->document_path);
            }
            $path = $request->file('document')->store('exams', 'public');
            $validated['document_path'] = $path;
        }

        $currentPoints = $exam->questions()->sum('points');
        if ($request->total_points < $currentPoints) {
            return redirect()->back()->with('error', "Impossible de réduire le barème : le total des points des questions existantes ({$currentPoints}) dépasse le nouveau barème ({$request->total_points}).");
        }

        $previousUserId = (int)$exam->user_id;

        $user = $request->user();
        $isDirecteur = $user->hasRole('Directeur');

        if ($isDirecteur && $request->filled('user_id')) {
            $validated['user_id'] = $request->input('user_id');
        }

        if ($isDirecteur) {
            $particularStudentIds = $request->input('particular_student_ids', []);
            $groupIds = $request->input('group_ids', []);
            $exam->groups()->sync($groupIds);
            $exam->particularStudents()->sync($particularStudentIds);

            $isExclusive = $request->boolean('is_exclusive_directeur') 
                || (!empty($particularStudentIds) && empty($groupIds));
            $validated['is_exclusive_directeur'] = $isExclusive;
        } elseif ($user->isTrainer()) {
            $allowedUserIds = $user->getAllowedTrainerUserIds();
            $trainerGroupIds = \App\Models\Group::whereIn('formateur_id', $allowedUserIds)->pluck('id')->toArray();
            $currentGroupIds = $exam->groups()->pluck('groups.id')->toArray();
            $otherGroupIds = array_diff($currentGroupIds, $trainerGroupIds);
            $newTrainerGroupIds = array_intersect($request->input('group_ids', []), $trainerGroupIds);
            $exam->groups()->sync(array_merge($otherGroupIds, $newTrainerGroupIds));
        }

        $exam->update($validated);

        if ($isDirecteur && (int)$exam->user_id !== $previousUserId && (int)$exam->user_id !== (int)$user->id && $exam->user) {
            try {
                $exam->user->notify(new ExamAssignedNotification($exam));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erreur d\'envoi de la notification d\'attribution d\'examen: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Examen mis à jour.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas supprimer cet examen.');
        }

        if ($exam->scheduled_at && $exam->scheduled_at->isPast() && !$request->user()->hasRole('Directeur') && $exam->examResults()->exists()) {
            return redirect()->back()->with('error', 'Impossible de supprimer cet examen car il a déjà commencé et contient des participations.');
        }

        if ($exam->document_path) {
            Storage::disk('public')->delete($exam->document_path);
        }
        $exam->delete();
        return redirect()->back()->with('success', 'Examen supprimé.');
    }

    public function duplicate(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas dupliquer cet examen.');
        }

        $newExam = $exam->replicate();
        $newExam->titre = $exam->titre . ' - Copie';
        $newExam->scheduled_at = null;
        $newExam->is_approved = false;
        $newExam->are_grades_published = false;
        $newExam->is_exclusive_directeur = $exam->is_exclusive_directeur;

        if ($request->user()->hasRole('Directeur') && $request->filled('user_id')) {
            $newExam->user_id = $request->input('user_id');
        }
        
        if ($exam->document_path && Storage::disk('public')->exists($exam->document_path)) {
            $extension = pathinfo($exam->document_path, PATHINFO_EXTENSION);
            $newPath = 'exams/' . uniqid() . '.' . $extension;
            Storage::disk('public')->copy($exam->document_path, $newPath);
            $newExam->document_path = $newPath;
        }

        $newExam->save();

        if ($request->user()->hasRole('Directeur')) {
            $newExam->particularStudents()->sync($exam->particularStudents->pluck('id'));
        }

        if ($request->user()->hasRole('Directeur') && (int)$newExam->user_id !== (int)$request->user()->id && $newExam->user) {
            try {
                $newExam->user->notify(new ExamAssignedNotification($newExam));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erreur d\'envoi de la notification d\'attribution d\'examen: ' . $e->getMessage());
            }
        }

        foreach ($exam->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->exam_id = $newExam->id;
            $newQuestion->save();

            if ($question->type === 'qcm') {
                foreach ($question->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->question_id = $newQuestion->id;
                    $newOption->save();
                }
            }
        }

        $msg = 'Examen dupliqué avec succès. Veuillez modifier la copie pour lui assigner un groupe et une date.';
        if ($request->user()->hasRole('Directeur')) {
            $msg = 'Examen dupliqué avec succès. Veuillez modifier la copie pour lui assigner un formateur, un groupe et une date.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Batch enter grades for paper exams.
     */
    public function enterGrades(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas saisir les notes de cet examen.');
        }

        if (!$exam->is_approved) {
            abort(403, 'Impossible d\'attribuer des notes car cet examen n\'a pas encore été validé par le directeur.');
        }

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.score' => 'nullable|numeric|min:0|max:20',
            'grades.*.bonus' => 'nullable|numeric|min:0',
        ]);

        $directors = User::role('Directeur')->get();
        $isDirector = $request->user()->hasRole('Directeur');

        if (!$isDirector) {
            $studentIds = collect($validated['grades'])->pluck('user_id');
            $hasParticularStudent = User::whereIn('id', $studentIds)->where('is_particulier', true)->exists();
            if ($hasParticularStudent) {
                abort(403, 'Seul le Directeur est autorisé à saisir les notes des apprenants particuliers.');
            }
        }

        foreach ($validated['grades'] as $gradeData) {
            if (!isset($gradeData['score']) || $gradeData['score'] === null) {
                continue;
            }

            $bonus = isset($gradeData['bonus']) ? (float)$gradeData['bonus'] : 0.00;

            // Find existing result to compare bonus
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $gradeData['user_id'])
                ->first();

            // Prevent non-directors from modifying an already graded paper exam
            if ($exam->type === 'paper' && $existing && $existing->score !== null && !$isDirector) {
                continue;
            }

            $oldBonus = $existing ? (float)$existing->bonus : 0.00;

            $result = ExamResult::updateOrCreate(
                ['exam_id' => $exam->id, 'user_id' => $gradeData['user_id']],
                [
                    'score' => $gradeData['score'],
                    'bonus' => $bonus,
                    'finished_at' => $existing ? $existing->finished_at : now()
                ]
            );

            // If bonus is newly added or changed
            if ($bonus > 0 && $bonus !== $oldBonus) {
                // Notify directors
                foreach ($directors as $director) {
                    $director->notify(new \App\Notifications\ExamBonusGivenNotification($result, $request->user()));
                }
            }

            // Notify student
            $result->user->notify(new \App\Notifications\ExamResultGradedNotification($result));
        }

        if (!$exam->are_grades_published) {
            $exam->update(['are_grades_published' => true]);

            // Notify directors that grades have been published
            foreach ($directors as $director) {
                $director->notify(new \App\Notifications\ExamGradesPublishedNotification($exam, $request->user()));
            }
        }

        return redirect()->back()->with('success', 'Notes enregistrées et publiées aux apprenants.');
    }

    public function getResults(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canViewExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas consulter la gestion des résultats de cet examen.');
        }

        if ($exam->is_exclusive_directeur && !$request->user()->hasRole('Directeur')) {
            abort(403, 'Cet examen est sous la supervision exclusive du Directeur.');
        }

        $user = $request->user();

        // Get students enrolled in the groups assigned to this exam
        $examGroupIds = $exam->groups()->pluck('groups.id');
        $studentsQuery = User::whereHas('studentGroups', function ($query) use ($examGroupIds) {
            $query->whereIn('groups.id', $examGroupIds);
        });

        if (!$user->hasRole('Directeur') && $user->isTrainer()) {
            $allowedTrainerIds = $user->getAllowedTrainerUserIds();
            $groupIds = \App\Models\Group::whereIn('formateur_id', $allowedTrainerIds)->pluck('id');
            $studentsQuery->whereHas('studentGroups', function ($query) use ($groupIds) {
                $query->whereIn('groups.id', $groupIds);
            });
            $studentsQuery->where('is_particulier', false);
        }

        $students = $studentsQuery->get();

        if ($user->hasRole('Directeur')) {
            $particularStudents = $exam->particularStudents()->get();
            $students = $students->merge($particularStudents)->unique('id');
        }

        $examController = new \App\Http\Controllers\ExamController();
        $examDate = $exam->scheduled_at ? $exam->scheduled_at->toDateString() : null;

        // Calculate score for students who saved answers, or auto-grade expired exams
        foreach ($students as $student) {
            $res = ExamResult::where('exam_id', $exam->id)->where('user_id', $student->id)->first();
            if ($res && $res->answers && is_array($res->answers) && count($res->answers) > 0) {
                $calcScore = $examController->calculateScore($exam, $res->answers);
                if ($res->score === null || ($res->status !== 'completed' && $res->score < $calcScore)) {
                    $res->update(['score' => $calcScore]);
                }
            } else if (!$res && $exam->isExpired() && $exam->type === 'online') {
                $hasJustifiedAbsence = Attendance::where('user_id', $student->id)
                    ->whereIn('group_id', $examGroupIds)
                    ->where('status', 'justifie')
                    ->when($examDate, fn($q) => $q->where('date', $examDate))
                    ->exists();

                $hasRattrapage = $exam->rattrapages()
                    ->whereHas('users', fn($q) => $q->where('users.id', $student->id))
                    ->exists();

                // Ne pas mettre 0 d'office si l'apprenant a une absence justifiée ou un rattrapage prévu
                if (!$hasJustifiedAbsence && !$hasRattrapage) {
                    ExamResult::create([
                        'exam_id' => $exam->id,
                        'user_id' => $student->id,
                        'score' => 0,
                        'status' => 'completed',
                        'finished_at' => $exam->scheduled_at ? $exam->scheduled_at->addMinutes($exam->duree_minutes) : now(),
                    ]);
                }
            }
        }

        // Get existing results for this exam (including newly updated ones)
        $results = ExamResult::where('exam_id', $exam->id)->get()->keyBy('user_id');

        // Rattrapages mapping for users
        $rattrapageUsers = DB::table('exam_rattrapage_user')
            ->join('exam_rattrapages', 'exam_rattrapages.id', '=', 'exam_rattrapage_user.exam_rattrapage_id')
            ->where('exam_rattrapages.exam_id', $exam->id)
            ->select('exam_rattrapage_user.user_id', 'exam_rattrapage_user.status as rattrapage_status', 'exam_rattrapages.scheduled_at as rattrapage_scheduled_at')
            ->get()
            ->keyBy('user_id');

        // Justified attendances mapping
        $justifiedAttendanceUserIds = Attendance::whereIn('group_id', $examGroupIds)
            ->where('status', 'justifie')
            ->when($examDate, fn($q) => $q->where('date', $examDate))
            ->pluck('user_id')
            ->toArray();

        // Merge results into students data
        $formattedResults = $students->map(function ($student) use ($results, $examController, $exam, $rattrapageUsers, $justifiedAttendanceUserIds) {
            $res = $results->get($student->id);
            $score = $res ? $res->score : null;

            if ($res && $score === null && $res->answers && is_array($res->answers)) {
                $score = $examController->calculateScore($exam, $res->answers);
            }

            $rattrapageInfo = $rattrapageUsers->get($student->id);
            $hasJustifiedAbsence = in_array($student->id, $justifiedAttendanceUserIds, true);

            return [
                'user_id' => $student->id,
                'name'    => $student->name,
                'is_particulier' => (bool) $student->is_particulier,
                'score'   => $score,
                'bonus'   => $res ? $res->bonus : 0.00,
                'status'  => $res ? $res->status : null,
                'answers' => $res ? $res->answers : null,
                'is_graded' => $res && $score !== null,
                'is_rattrapage' => $res ? (bool) $res->is_rattrapage : false,
                'has_justified_absence' => $hasJustifiedAbsence,
                'rattrapage_scheduled_at' => $rattrapageInfo ? Carbon::parse($rattrapageInfo->rattrapage_scheduled_at)->format('d/m/Y à H:i') : null,
                'rattrapage_status' => $rattrapageInfo?->rattrapage_status,
            ];
        });

        return response()->json($formattedResults->values());
    }

    /**
     * Grade open questions for a specific student exam result.
     */
    public function gradeOpenQuestions(Request $request, Exam $exam, User $user): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas noter cet examen.');
        }

        if ($user->is_particulier && !$request->user()->hasRole('Directeur')) {
            abort(403, 'Seul le Directeur est autorisé à évaluer un apprenant particulier.');
        }

        $validated = $request->validate([
            'open_question_scores' => 'required|array',
            'open_question_scores.*' => 'nullable|numeric|min:0',
            'score' => 'nullable|numeric|min:0|max:20',
            'bonus' => 'nullable|numeric|min:0',
        ]);

        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$result) {
            return redirect()->back()->with('error', 'Aucune soumission trouvée pour cet apprenant.');
        }

        $answers = $result->answers ?? [];
        if (!is_array($answers)) {
            $answers = [];
        }

        $questionScores = $answers['_question_scores'] ?? [];
        foreach ($validated['open_question_scores'] as $questionId => $scoreValue) {
            if ($scoreValue !== null && $scoreValue !== '') {
                $question = $exam->questions->firstWhere('id', $questionId);
                if ($question && $question->type === 'open') {
                    $maxPoints = (float)$question->points;
                    $givenScore = (float)$scoreValue;
                    if ($givenScore > $maxPoints) {
                        return redirect()->back()->with('error', "La note attribuée à une question ouverte ne peut pas dépasser son barème ({$maxPoints} pts).");
                    }
                    $questionScores[(string)$questionId] = $givenScore;
                }
            }
        }
        $answers['_question_scores'] = $questionScores;

        if (isset($validated['score']) && $validated['score'] !== null) {
            $finalScore = (float)$validated['score'];
        } else {
            $qcmEarned = 0;
            $openEarned = 0;

            $exam->load('questions.options');
            foreach ($exam->questions as $question) {
                if ($question->type === 'qcm') {
                    $correctOptionIds = $question->options()->where('is_correct', true)->pluck('id')->toArray();
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
                    if ($questionScore < 0) $questionScore = 0;
                    if ($questionScore > $questionPoints) $questionScore = $questionPoints;
                    $qcmEarned += $questionScore;
                } else {
                    $qScore = $questionScores[(string)$question->id] ?? 0;
                    $openEarned += min((float)$qScore, (float)$question->points);
                }
            }

            $totalExamPoints = (float)$exam->questions->sum('points');
            if ($totalExamPoints > 0) {
                $finalScore = (($qcmEarned + $openEarned) / $totalExamPoints) * 20;
            } else {
                $finalScore = 0;
            }
        }

        $result->answers = $answers;
        $result->score = round($finalScore, 2);
        if (isset($validated['bonus'])) {
            $result->bonus = (float)$validated['bonus'];
        }
        $result->save();

        if ($result->user) {
            $result->user->notify(new \App\Notifications\ExamResultGradedNotification($result));
        }

        return redirect()->back()->with('success', 'Correction des questions ouvertes enregistrée avec succès.');
    }

    /**
     * Unlock a blocked exam result for a student.
     */
    public function unlock(Request $request, Exam $exam, \App\Models\User $user): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas débloquer cet examen.');
        }

        if (!$user->hasRole(['Apprenant', 'Stagiaire'])) {
            abort(403, 'Utilisateur invalide.');
        }

        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if ($result) {
            $result->update([
                'status' => 'started',
                'answers' => null,
                'score' => null,
                'bonus' => 0,
                'started_at' => now(),
                'finished_at' => null,
            ]);
            return redirect()->back()->with('success', 'L\'examen a été réinitialisé pour cet étudiant. Il peut recommencer à zéro.');
        }

        return redirect()->back()->with('error', 'Aucune tentative trouvée pour cet étudiant.');
    }

    /**
     * Unblock a student's exam without resetting their progress (resume mode).
     */
    public function unblock(Request $request, Exam $exam, \App\Models\User $user): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas débloquer cet examen.');
        }

        if (!$user->hasRole(['Apprenant', 'Stagiaire'])) {
            abort(403, 'Utilisateur invalide.');
        }

        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if ($result) {
            $result->update(['status' => 'started']);
            return redirect()->back()->with('success', 'L\'examen a été débloqué. L\'apprenant peut reprendre là où il s\'était arrêté.');
        }

        return redirect()->back()->with('error', 'Aucune tentative trouvée pour cet étudiant.');
    }


    /**
     * Manage Questions for an exam.
     */
    public function storeQuestion(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas gérer les questions de cet examen.');
        }

        if ($exam->scheduled_at && $exam->scheduled_at->isPast() && !$request->user()->hasRole('Directeur') && $exam->examResults()->exists()) {
            return redirect()->back()->with('error', 'Impossible de modifier la banque de questions car cet examen a déjà commencé et contient des participations.');
        }

        $validated = $request->validate([
            'enonce' => 'required|string',
            'points' => 'required|numeric|min:0',
            'type' => 'required|in:qcm,open',
            'expected_answer' => 'nullable|string',
            'options' => 'array',
            'options.*.texte' => 'required_if:type,qcm|nullable|string',
            'options.*.is_correct' => 'required_if:type,qcm|nullable|boolean',
        ]);

        $currentPoints = $exam->questions()->sum('points');
        if (($currentPoints + $validated['points']) > $exam->total_points) {
            return redirect()->back()->with('error', "Le total des points des questions (" . ($currentPoints + $validated['points']) . ") ne peut pas dépasser le barème de l'examen ({$exam->total_points}).");
        }

        $question = $exam->questions()->create([
            'enonce' => $validated['enonce'],
            'points' => $validated['points'],
            'type' => $validated['type'],
            'expected_answer' => $validated['expected_answer'] ?? null,
            'ordre' => $exam->questions()->count() + 1,
        ]);

        if ($validated['type'] === 'qcm' && !empty($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $question->options()->create($optionData);
            }
        }

        return redirect()->back();
    }

    public function approve(Request $request, Exam $exam): RedirectResponse
    {
        if (!$request->user()->hasRole('Directeur')) {
            abort(403, 'Seul le directeur peut valider les examens.');
        }

        $exam->update(['is_approved' => true]);

        if ($request->has('group_ids')) {
            $exam->groups()->sync($request->input('group_ids'));
        }

        try {
            // Notify students enrolled in the assigned groups
            $students = User::role('Apprenant')
                ->whereHas('studentGroups', function ($query) use ($exam) {
                    $query->whereIn('groups.id', $exam->groups->pluck('id'));
                })->get();

            foreach ($students as $student) {
                $student->notify(new NewExamAvailableNotification($exam));
            }

            if ($exam->user && $exam->user_id !== $request->user()->id) {
                $exam->user->notify(new \App\Notifications\ExamApprovedNotification($exam));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi des notifications de validation d\'examen : ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'L\'examen a été validé avec succès.');
    }

    /**
     * Download or view the exam document (énoncé).
     */
    public function download(Request $request, Exam $exam)
    {
        if (!$request->user()->hasRole('Secrétaire') && !$this->canViewExam($request->user(), $exam)) {
            abort(403, 'Vous ne pouvez pas télécharger cet énoncé.');
        }
        if ($exam->type !== 'paper' || !$exam->document_path) {
            return redirect()->back()->with('error', "Aucun énoncé disponible pour cet examen.");
        }

        $filePath = storage_path('app/public/' . $exam->document_path);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', "Le fichier n'existe pas sur le serveur.");
        }

        $mimeType = mime_content_type($filePath);
        $headers = [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ];

        return response()->file($filePath, $headers);
    }

    /**
     * Get all rattrapage sessions for an exam.
     */
    public function getRattrapages(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$this->canViewExam($request->user(), $exam)) {
            abort(403, 'Action non autorisée.');
        }

        $rattrapages = $exam->rattrapages()
            ->with(['users', 'creator', 'results'])
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'titre' => $r->titre,
                    'scheduled_at' => $r->scheduled_at ? $r->scheduled_at->format('Y-m-d\TH:i') : null,
                    'scheduled_at_formatted' => $r->scheduled_at ? $r->scheduled_at->format('d/m/Y à H:i') : null,
                    'duree_minutes' => $r->duree_minutes,
                    'instructions' => $r->instructions,
                    'created_by' => $r->creator?->name,
                    'can_start' => $r->can_start,
                    'has_ended' => $r->has_ended,
                    'students_count' => $r->users->count(),
                    'students' => $r->users->map(function ($u) use ($r) {
                        $res = $r->results->firstWhere('user_id', $u->id);
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'status' => $u->pivot->status,
                            'motif_justification' => $u->pivot->motif_justification,
                            'score' => $res?->score,
                        ];
                    }),
                ];
            });

        return response()->json($rattrapages);
    }

    /**
     * Get learners eligible for makeup session (justified absences or group students).
     */
    public function getEligibleRattrapageStudents(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        if (!$this->canViewExam($request->user(), $exam)) {
            abort(403, 'Action non autorisée.');
        }

        $examGroupIds = $exam->groups()->pluck('groups.id')->toArray();
        $examDate = $exam->scheduled_at ? $exam->scheduled_at->toDateString() : null;

        // Get all students enrolled in assigned groups
        $students = User::whereHas('studentGroups', function ($query) use ($examGroupIds) {
            $query->whereIn('groups.id', $examGroupIds);
        })->with('studentGroups')->get();

        // Existing results
        $results = ExamResult::where('exam_id', $exam->id)->get()->keyBy('user_id');

        // Existing scheduled rattrapages
        $scheduledUserIds = DB::table('exam_rattrapage_user')
            ->join('exam_rattrapages', 'exam_rattrapages.id', '=', 'exam_rattrapage_user.exam_rattrapage_id')
            ->where('exam_rattrapages.exam_id', $exam->id)
            ->pluck('exam_rattrapage_user.user_id')
            ->toArray();

        // Attendances with status 'justifie' on the exact exam date
        $justifiedAttendances = Attendance::whereIn('group_id', $examGroupIds)
            ->where('status', 'justifie')
            ->when($examDate, function ($q) use ($examDate) {
                $q->where('date', $examDate);
            })
            ->get()
            ->keyBy('user_id');

        // Fallback: any justified attendance for the module/group
        $fallbackJustified = Attendance::whereIn('group_id', $examGroupIds)
            ->where('status', 'justifie')
            ->latest('date')
            ->get()
            ->keyBy('user_id');

        $eligibleList = $students->map(function ($student) use ($results, $scheduledUserIds, $justifiedAttendances, $fallbackJustified) {
            $res = $results->get($student->id);
            $isCompleted = $res && $res->status === 'completed' && !$res->is_rattrapage && $res->score !== null && $res->score > 0;
            $isAlreadyScheduled = in_array($student->id, $scheduledUserIds, true);

            $justifiedExact = $justifiedAttendances->get($student->id);
            $justifiedGeneral = $fallbackJustified->get($student->id);

            $hasJustifiedAbsenceOnExamDate = $justifiedExact !== null;
            $hasAnyJustifiedAbsence = $justifiedGeneral !== null;

            $motif = null;
            if ($hasJustifiedAbsenceOnExamDate) {
                $motif = "Absence justifiée le jour de l'épreuve (" . $justifiedExact->date->format('d/m/Y') . ")";
            } elseif ($hasAnyJustifiedAbsence) {
                $motif = "Absence justifiée enregistrée le " . $justifiedGeneral->date->format('d/m/Y');
            }

            return [
                'id' => $student->id,
                'name' => $student->name,
                'telephone' => $student->telephone,
                'group_name' => $student->studentGroups->first()?->nom_groupe ?? 'N/A',
                'has_justified_absence' => $hasJustifiedAbsenceOnExamDate || $hasAnyJustifiedAbsence,
                'has_justified_absence_on_exam_date' => $hasJustifiedAbsenceOnExamDate,
                'justification_motif' => $motif,
                'attendance_id' => $justifiedExact?->id ?? $justifiedGeneral?->id,
                'is_completed' => $isCompleted,
                'score' => $res?->score,
                'is_already_scheduled' => $isAlreadyScheduled,
                // Recommended for selection if they had a justified absence and haven't already taken or scheduled
                'is_recommended' => ($hasJustifiedAbsenceOnExamDate || $hasAnyJustifiedAbsence) && !$isCompleted && !$isAlreadyScheduled,
            ];
        });

        // Sort: recommended first, then justified absences, then alphabetically
        $sorted = $eligibleList->sort(function ($a, $b) {
            if ($a['is_recommended'] !== $b['is_recommended']) {
                return $a['is_recommended'] ? -1 : 1;
            }
            if ($a['has_justified_absence'] !== $b['has_justified_absence']) {
                return $a['has_justified_absence'] ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        })->values();

        return response()->json($sorted);
    }

    /**
     * Store a new makeup exam session (rattrapage).
     */
    public function storeRattrapage(Request $request, Exam $exam)
    {
        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Action non autorisée.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'duree_minutes' => 'required|integer|min:1|max:480',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:users,id',
            'titre' => 'nullable|string|max:255',
            'instructions' => 'nullable|string|max:1000',
        ], [
            'student_ids.required' => 'Veuillez sélectionner au moins un apprenant éligible.',
            'student_ids.min' => 'Veuillez sélectionner au moins un apprenant éligible.',
        ]);

        $rattrapage = ExamRattrapage::create([
            'exam_id' => $exam->id,
            'titre' => $validated['titre'] ?: ("Session de rattrapage - " . $exam->titre),
            'scheduled_at' => Carbon::parse($validated['scheduled_at']),
            'duree_minutes' => (int)$validated['duree_minutes'],
            'instructions' => $validated['instructions'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $examGroupIds = $exam->groups()->pluck('groups.id')->toArray();
        $examDate = $exam->scheduled_at ? $exam->scheduled_at->toDateString() : null;

        foreach ($validated['student_ids'] as $studentId) {
            $att = Attendance::where('user_id', $studentId)
                ->whereIn('group_id', $examGroupIds)
                ->where('status', 'justifie')
                ->when($examDate, function ($q) use ($examDate) {
                    $q->orderByRaw("CASE WHEN date = ? THEN 0 ELSE 1 END", [$examDate]);
                })
                ->first();

            $motif = $att 
                ? "Absence justifiée le " . $att->date->format('d/m/Y')
                : "Absence justifiée validée par l'encadrement";

            $rattrapage->users()->attach($studentId, [
                'attendance_id' => $att?->id,
                'motif_justification' => $motif,
                'status' => 'scheduled',
            ]);
        }

        // Notify learners
        $students = User::whereIn('id', $validated['student_ids'])->get();
        foreach ($students as $student) {
            try {
                $student->notify(new NewExamRattrapageNotification($rattrapage));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erreur notification rattrapage : ' . $e->getMessage());
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Session de rattrapage planifiée avec succès.',
                'rattrapage' => $rattrapage->load('users'),
            ]);
        }

        return redirect()->back()->with('success', 'Session de rattrapage planifiée avec succès.');
    }

    /**
     * Cancel / delete a makeup exam session.
     */
    public function destroyRattrapage(Request $request, Exam $exam, ExamRattrapage $rattrapage)
    {
        if (!$this->canModifyExam($request->user(), $exam)) {
            abort(403, 'Action non autorisée.');
        }

        if ($rattrapage->exam_id !== $exam->id) {
            abort(404);
        }

        $rattrapage->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Session de rattrapage supprimée.');
    }
}
