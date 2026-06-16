<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ChapterGroupProgress;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\ExerciseSubmission;
use App\Models\Exam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Redirect trainers (Formateurs, course_assistant, course_substitute) to their own dashboard
        if ($user->isTrainer()) {
            return redirect()->route('trainer.groups');
        }

        // 1. Prochains cours (schedules via group)
        $nextSchedules = Schedule::with(['room', 'formateur', 'group'])
            ->whereHas('group', function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // 2. Nombre d'absences
        $absenceCount = Attendance::where(function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where(function ($query) {
                $query->where('status', 'absent_non_justifie');
            })->count();

        // 3. Avancement du module (pourcentage)
        // On récupère le groupe de l'apprenant (supposons 1 groupe actif pour simplifier)
        $group = $user->studentGroups()->first();
        $progress = 0;
        $individualProgress = 0;

        if ($group) {
            $totalChapters = $group->module->chapters()->count();
            $approvedChapters = ChapterGroupProgress::where('group_id', $group->id)
                ->where('status', 'approved')
                ->count();
            
            $progress = $totalChapters > 0 
                ? round(($approvedChapters / $totalChapters) * 100, 1) 
                : 0;

            // Calcul progression individuelle (Exercices complétés vs Total exercices du module)
            $totalExercises = $group->module->chapters()->whereNotNull('exercise_type')->count();
            if ($totalExercises > 0) {
                $submittedExercisesCount = ExerciseSubmission::where('user_id', $user->id)
                    ->whereHas('chapter', function($q) use ($group) {
                        $q->where('module_id', $group->module_id);
                    })
                    ->distinct('chapter_id')
                    ->count('chapter_id');
                    
                $individualProgress = round(($submittedExercisesCount / $totalExercises) * 100, 1);
            }
        }

        // 4. Examens à venir pour le groupe actuel
        $upcomingExams = [];
        if ($group) {
            $exams = Exam::whereHas('groups', function ($query) use ($group) {
                    $query->where('groups.id', $group->id);
                })
                ->whereDoesntHave('examResults', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('is_active', true)
                ->where('is_approved', true)
                ->get();
                
            // On filtre les examens expirés
            $upcomingExams = $exams->filter(function($exam) {
                return !$exam->has_ended;
            })->values();
        }

        // 5. Résultats récents d'exercices
        $recentExercises = ExerciseSubmission::with('chapter')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // 6. Résultats récents d'examens
        $recentExams = \App\Models\ExamResult::with('exam')
            ->where('user_id', $user->id)
            ->whereHas('exam', function($q) {
                $q->where('is_practice', false);
            })
            ->latest()
            ->take(5)
            ->get();

        // 7. Statistiques et Moyennes
        $gradedExercises = ExerciseSubmission::where('user_id', $user->id)
            ->whereNotIn('status', ['pending', 'rejected'])
            ->get();
        
        $exerciseAvg = null;
        if ($gradedExercises->count() > 0) {
            $sum = 0;
            $count = 0;
            foreach($gradedExercises as $ex) {
                $max = $ex->chapter ? $ex->chapter->exercise_points : 20;
                if ($max > 0) {
                    $sum += ($ex->grade / $max) * 20;
                    $count++;
                }
            }
            $exerciseAvg = $count > 0 ? round($sum / $count, 1) : null;
        }

        $examAvg = null;
        $gradedExams = \App\Models\ExamResult::with('exam')
            ->where('user_id', $user->id)
            ->whereHas('exam', function($q) {
                $q->where('is_practice', false);
            })
            ->get();
            
        if ($gradedExams->count() > 0) {
            $sum = 0;
            $count = 0;
            foreach($gradedExams as $res) {
                if ($res->exam && $res->exam->total_points > 0) {
                    $sum += ($res->score / $res->exam->total_points) * 20;
                    $count++;
                }
            }
            $examAvg = $count > 0 ? round($sum / $count, 1) : null;
        }

        return Inertia::render('Student/Dashboard', [
            'nextSchedules'   => $nextSchedules,
            'absenceCount'    => $absenceCount,
            'progress'        => $progress,
            'individualProgress' => $individualProgress,
            'group'           => $group,
            'upcomingExams'   => $upcomingExams,
            'recentExercises' => $recentExercises,
            'recentExams'     => $recentExams,
            'stats'           => [
                'exerciseAvg' => $exerciseAvg,
                'examAvg'     => $examAvg,
                'exercisesDone' => $gradedExercises->count(),
                'examsDone'     => $gradedExams->count(),
            ]
        ]);
    }

    /**
     * Display the student's certificates.
     */
    public function myCertificates(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $certificates = $user->certificates()->with('module')->latest()->get();

        return Inertia::render('Student/MyCertificates', [
            'certificates' => $certificates,
        ]);
    }
}
