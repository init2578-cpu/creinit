<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Module;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewExamAvailableNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminExamController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole('Secrétaire')) {
            $examQuery = Exam::with(['module', 'questions', 'groups'])->orderBy('created_at', 'desc');
        } else {
            $examQuery = Exam::with(['module', 'questions', 'examResults.user', 'groups'])->orderBy('created_at', 'desc');
        }
        $moduleQuery = Module::query();
        $groupsQuery = Group::query();

        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire') && $user->isTrainer()) {
            $moduleIds = $user->groupsAsFormateur()->pluck('module_id');
            $examQuery->whereIn('module_id', $moduleIds);
            $moduleQuery->whereIn('id', $moduleIds);
            $groupsQuery->where('formateur_id', $user->id);
        }

        return Inertia::render('Scolarite/ExamsIndex', [
            'exams'   => $examQuery->get()->map(function ($exam) {
                $exam->expected_results_count = User::role('Apprenant')
                    ->whereHas('studentGroups', function ($query) use ($exam) {
                        $query->whereIn('groups.id', $exam->groups->pluck('id'));
                    })->count();
                return $exam;
            }),
            'modules' => $moduleQuery->get(),
            'groups'  => $groupsQuery->get(),
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
        ]);

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('exams', 'public');
            $validated['document_path'] = $path;
        }

        $user = $request->user();
        $validated['is_approved'] = $user->hasRole('Directeur');
        $validated['user_id'] = $user->id;

        $exam = Exam::create($validated);
        $exam->load('module');

        $user = $request->user();
        if ($user->hasRole('Directeur')) {
            $exam->groups()->sync($request->input('group_ids', []));
        } elseif ($user->isTrainer()) {
            $trainerGroupIds = \App\Models\Group::where('formateur_id', $user->id)->pluck('id')->toArray();
            $currentGroupIds = $exam->groups()->pluck('groups.id')->toArray();
            $otherGroupIds = array_diff($currentGroupIds, $trainerGroupIds);
            $newTrainerGroupIds = array_intersect($request->input('group_ids', []), $trainerGroupIds);
            $exam->groups()->sync(array_merge($otherGroupIds, $newTrainerGroupIds));
        }

        if ($exam->is_approved) {
            // Notify students enrolled in the assigned groups
            $students = User::role('Apprenant')
                ->whereHas('studentGroups', function ($query) use ($exam) {
                    $query->whereIn('groups.id', $exam->groups->pluck('id'));
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

        $exam->update($validated);

        $user = $request->user();
        if ($user->hasRole('Directeur')) {
            $exam->groups()->sync($request->input('group_ids', []));
        } elseif ($user->isTrainer()) {
            $trainerGroupIds = \App\Models\Group::where('formateur_id', $user->id)->pluck('id')->toArray();
            $currentGroupIds = $exam->groups()->pluck('groups.id')->toArray();
            $otherGroupIds = array_diff($currentGroupIds, $trainerGroupIds);
            $newTrainerGroupIds = array_intersect($request->input('group_ids', []), $trainerGroupIds);
            $exam->groups()->sync(array_merge($otherGroupIds, $newTrainerGroupIds));
        }

        return redirect()->back()->with('success', 'Examen mis à jour.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if ($exam->document_path) {
            Storage::disk('public')->delete($exam->document_path);
        }
        $exam->delete();
        return redirect()->back()->with('success', 'Examen supprimé.');
    }

    /**
     * Batch enter grades for paper exams.
     */
    public function enterGrades(Request $request, Exam $exam): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
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

        foreach ($validated['grades'] as $gradeData) {
            if (!isset($gradeData['score']) || $gradeData['score'] === null) {
                continue;
            }

            $bonus = isset($gradeData['bonus']) ? (float)$gradeData['bonus'] : 0.00;

            // Find existing result to compare bonus
            $existing = ExamResult::where('exam_id', $exam->id)
                ->where('user_id', $gradeData['user_id'])
                ->first();

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

        $user = $request->user();

        // Get students enrolled in the module, but restrict to formateur's groups if necessary
        $studentsQuery = User::whereHas('studentGroups', function ($query) use ($exam) {
            $query->where('module_id', $exam->module_id);
        });

        if (!$user->hasRole('Directeur') && $user->isTrainer()) {
            $groupIds = $user->groupsAsFormateur()->pluck('id');
            $studentsQuery->whereHas('studentGroups', function ($query) use ($groupIds) {
                $query->whereIn('groups.id', $groupIds);
            });
        }

        $students = $studentsQuery->get();

        // If the exam has officially ended, auto-grade students who didn't submit with 0
        if ($exam->isExpired() && $exam->type === 'online') {
            foreach ($students as $student) {
                ExamResult::firstOrCreate(
                    ['exam_id' => $exam->id, 'user_id' => $student->id],
                    ['score' => 0, 'finished_at' => $exam->scheduled_at->addMinutes($exam->duree_minutes)]
                );
            }
        }

        // Get existing results for this exam (including newly created ones)
        $results = ExamResult::where('exam_id', $exam->id)->get()->keyBy('user_id');

        // Merge results into students data
        $formattedResults = $students->map(function ($student) use ($results) {
            $res = $results->get($student->id);
            return [
                'user_id' => $student->id,
                'name'    => $student->name,
                'score'   => $res ? $res->score : null,
                'bonus'   => $res ? $res->bonus : 0.00,
                'status'  => $res ? $res->status : null,
            ];
        });

        return response()->json($formattedResults->values());
    }

    /**
     * Unlock a blocked exam result for a student.
     */
    public function unlock(Request $request, Exam $exam, \App\Models\User $user): RedirectResponse
    {
        if ($request->user()->hasRole('Secrétaire')) {
            abort(403, 'Action non autorisée pour les secrétaires.');
        }

        if (!$user->hasRole(['Apprenant', 'Stagiaire'])) {
            abort(403, 'Utilisateur invalide.');
        }

        $result = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if ($result) {
            $result->delete();
            return redirect()->back()->with('success', 'L\'examen a été réinitialisé pour cet étudiant. Il peut recommencer à zéro.');
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

        $validated = $request->validate([
            'enonce' => 'required|string',
            'points' => 'required|numeric|min:0',
            'type' => 'required|in:qcm,open',
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
            'ordre' => $exam->questions()->count() + 1,
        ]);

        if ($validated['type'] === 'qcm' && !empty($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $question->options()->create($optionData);
            }
        }

        return redirect()->back()->with('success', 'Question ajoutée.');
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

        return redirect()->back()->with('success', 'L\'examen a été validé avec succès.');
    }
}
