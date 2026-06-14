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

        if ($group) {
            $totalChapters = $group->module->chapters()->count();
            $approvedChapters = ChapterGroupProgress::where('group_id', $group->id)
                ->where('status', 'approved')
                ->count();
            
            $progress = $totalChapters > 0 
                ? round(($approvedChapters / $totalChapters) * 100, 1) 
                : 0;
        }

        // 4. Examens à venir pour le groupe actuel
        $upcomingExams = [];
        if ($group) {
            $upcomingExams = Exam::whereHas('groups', function ($query) use ($group) {
                    $query->where('groups.id', $group->id);
                })
                ->where('is_active', true)
                ->where('is_approved', true)
                ->get();
        }

        // 5. Résultats récents d'exercices
        $recentExercises = ExerciseSubmission::with('chapter')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Student/Dashboard', [
            'nextSchedules'   => $nextSchedules,
            'absenceCount'    => $absenceCount,
            'progress'        => $progress,
            'group'           => $group,
            'upcomingExams'   => $upcomingExams,
            'recentExercises' => $recentExercises,
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
