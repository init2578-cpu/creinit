<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\StoreBatchAttendanceRequest;
use App\Models\Attendance;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display groups assigned to the trainer.
     */
    public function trainerGroups(Request $request): Response
    {
        $user = $request->user();
        $trainerIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $trainerIds[] = $user->internshipRecord->tuteur_id;
        }

        // Only show groups assigned to the trainer which HAVE a schedule for this trainer
        $groups = Group::with('module')
            ->whereIn('formateur_id', $trainerIds)
            ->whereHas('schedules', function ($query) use ($trainerIds) {
                $query->whereIn('formateur_id', $trainerIds);
            })
            ->get();

        return Inertia::render('Attendances/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the attendance form for a specific group.
     */
    public function takeAttendance(Group $group): Response
    {
        $user = auth()->user();
        $trainerIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $trainerIds[] = $user->internshipRecord->tuteur_id;
        }

        // Check if the trainer/substitute has a schedule for this group
        $hasSchedule = \App\Models\Schedule::where('group_id', $group->id)
            ->whereIn('formateur_id', $trainerIds)
            ->exists();

        if (!$hasSchedule) {
            abort(403, "Vous n'avez pas de créneau d'emploi du temps pour ce groupe.");
        }

        $group->load('students');

        return Inertia::render('Attendances/TakeAttendance', [
            'group'    => $group,
            'students' => $group->students,
        ]);
    }

    /**
     * Store multiple attendance records (Bulk).
     * Protected by EnsureWithinPremises middleware.
     */
    public function storeBatch(StoreBatchAttendanceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $trainerIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $trainerIds[] = $user->internshipRecord->tuteur_id;
        }

        $groupId = $request->validated('group_id');
        $date = $request->validated('date');
        
        // Resolve schedule_id based on the day of the week
        $carbonDate = \Carbon\Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        
        $schedule = \App\Models\Schedule::where('group_id', $groupId)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('formateur_id', $trainerIds)
            ->first();

        if (!$schedule) {
            $schedule = \App\Models\Schedule::where('group_id', $groupId)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        }

        $scheduleId = $schedule ? $schedule->id : null;

        // Check if the trainer/substitute has a schedule for this group
        $hasSchedule = \App\Models\Schedule::where('group_id', $groupId)
            ->whereIn('formateur_id', $trainerIds)
            ->exists();

        if (!$hasSchedule) {
            abort(403, "Vous n'avez pas de créneau d'emploi du temps pour ce groupe.");
        }

        foreach ($request->validated('attendances') as $data) {
            Attendance::updateOrCreate(
                [
                    'user_id'     => $data['user_id'],
                    'group_id'    => $groupId,
                    'schedule_id' => $scheduleId,
                    'date'        => $date,
                ],
                [
                    'status'    => $data['status'],
                    'latitude'  => $request->validated('latitude'),
                    'longitude' => $request->validated('longitude'),
                ]
            );
        }

        // Clear dashboard cache to reflect new attendance data
        \Illuminate\Support\Facades\Cache::forget('director_dashboard_kpis');

        $group = Group::find($groupId);
        if ($group) {
            Group::checkQuotaAndNotify($group);
        }

        return redirect()
            ->route('attendances.trainer-groups')
            ->with('success', 'Émargement enregistré avec succès.');
    }

    /**
     * Display attendance history for a group (Original index).
     */
    public function index(int $groupId): Response
    {
        $user = auth()->user();
        $trainerIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $trainerIds[] = $user->internshipRecord->tuteur_id;
        }

        // Allow Directeur/Secrétaire to see history anyway, but restrict trainers/substitutes
        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            $hasSchedule = \App\Models\Schedule::where('group_id', $groupId)
                ->whereIn('formateur_id', $trainerIds)
                ->exists();

            if (!$hasSchedule) {
                abort(403, "Vous n'avez pas de créneau d'emploi du temps pour ce groupe.");
            }
        }

        $attendances = Attendance::with(['user', 'group'])
            ->where('group_id', $groupId)
            ->orderByDesc('date')
            ->paginate(20);

        return Inertia::render('Attendances/History', [
            'attendances' => $attendances,
            'group_id'    => $groupId,
        ]);
    }

    /**
     * Individual store (Legacy/Self-attendance).
     */
    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        // ... (conservé pour émargement individuel si nécessaire)
        return redirect()->back();
    }
}

