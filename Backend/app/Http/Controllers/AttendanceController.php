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

        // Only show active groups assigned to the trainer which HAVE a schedule for this trainer
        $groups = Group::with('module')
            ->where('status', 'active')
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
    public function takeAttendance(Group $group): Response|\Illuminate\Http\RedirectResponse
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

        // Enforce same timeframe and validation rules for trainers (Directeur/Secrétaire bypass this)
        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            $now = \Carbon\Carbon::now();
            $today = $now->toDateString();
            $dayOfWeek = $now->dayOfWeekIso;

            $schedules = \App\Models\Schedule::where('group_id', $group->id)
                ->where('day_of_week', $dayOfWeek)
                ->whereIn('formateur_id', $trainerIds)
                ->get();

            if ($schedules->isEmpty()) {
                $schedules = \App\Models\Schedule::where('group_id', $group->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->get();
            }

            if ($schedules->isEmpty()) {
                return redirect()
                    ->route('attendances.trainer-groups')
                    ->with('error', "Aucun cours planifié aujourd'hui pour ce groupe.");
            }

            // Find matching schedule based on current time (including buffers)
            $bufferBefore = (int) \App\Models\Setting::getValue('attendance_buffer_before', 10);
            $bufferAfter = (int) \App\Models\Setting::getValue('attendance_buffer_after', 15);

            $schedule = null;
            foreach ($schedules as $s) {
                $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
                $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->setDateFrom($now)->addMinutes($bufferAfter);
                if ($now->between($startTime, $endTime)) {
                    $schedule = $s;
                    break;
                }
            }

            // If outside all schedule slots, pick the first one to show correct timeframe error
            if (!$schedule) {
                $schedule = $schedules->first();
            }

            // Check if attendance was already taken/validated for this specific schedule
            $alreadyTaken = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $today)
                ->exists();

            if ($alreadyTaken) {
                return redirect()
                    ->route('attendances.trainer-groups')
                    ->with('error', "L'émargement pour le créneau " . substr($schedule->start_time, 0, 5) . " - " . substr($schedule->end_time, 0, 5) . " a déjà été validé aujourd'hui et ne peut plus être modifié.");
            }

            // Check timeframe with buffer
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->end_time)->setDateFrom($now)->addMinutes($bufferAfter);

            if (!$now->between($startTime, $endTime)) {
                $msg = sprintf(
                    "L'émargement n'est autorisé que durant le créneau du cours (%s à %s, avec une tolérance de %d min avant et %d min après).",
                    substr($schedule->start_time, 0, 5),
                    substr($schedule->end_time, 0, 5),
                    $bufferBefore,
                    $bufferAfter
                );
                return redirect()
                    ->route('attendances.trainer-groups')
                    ->with('error', $msg);
            }
        }

        $group->load('students');
        $students = $group->students;

        $trainerId = $group->formateur_id;
        if ($trainerId) {
            $assistants = \App\Models\User::role('Stagiaire')
                ->whereHas('internshipRecord', function($q) use ($trainerId) {
                    $q->where('internship_type', 'course_assistant')
                      ->where('tuteur_id', $trainerId);
                })
                ->get();
            
            foreach ($assistants as $assistant) {
                $assistant->name = "[ASSISTANT] " . $assistant->name;
                $students->prepend($assistant);
            }
        }

        return Inertia::render('Attendances/TakeAttendance', [
            'group'    => $group,
            'students' => $students,
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
        
        // Resolve schedule based on day of week and current time
        $carbonDate = \Carbon\Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        
        $schedules = \App\Models\Schedule::where('group_id', $groupId)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('formateur_id', $trainerIds)
            ->get();

        if ($schedules->isEmpty()) {
            $schedules = \App\Models\Schedule::where('group_id', $groupId)
                ->where('day_of_week', $dayOfWeek)
                ->get();
        }

        $now = \Carbon\Carbon::now();
        $bufferBefore = (int) \App\Models\Setting::getValue('attendance_buffer_before', 10);
        $bufferAfter = (int) \App\Models\Setting::getValue('attendance_buffer_after', 15);

        // Find active schedule based on current time
        $schedule = null;
        foreach ($schedules as $s) {
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->setDateFrom($now)->addMinutes($bufferAfter);
            if ($now->between($startTime, $endTime)) {
                $schedule = $s;
                break;
            }
        }

        // Fallback to first schedule of the day if none matches the active slot
        if (!$schedule) {
            $schedule = $schedules->first();
        }

        $scheduleId = $schedule ? $schedule->id : null;

        // Check if the trainer/substitute has a schedule for this group
        $hasSchedule = \App\Models\Schedule::where('group_id', $groupId)
            ->whereIn('formateur_id', $trainerIds)
            ->exists();

        if (!$hasSchedule) {
            abort(403, "Vous n'avez pas de créneau d'emploi du temps pour ce groupe.");
        }

        // Timeframe and status restrictions for trainers (Directeur and Secrétaire bypass this check)
        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            if (!$schedule) {
                return redirect()->back()->with('error', "Aucun créneau d'emploi du temps trouvé pour cette date.");
            }

            // 1. Restriction: only present or absent_non_justifie is allowed for trainers
            foreach ($request->validated('attendances') as $data) {
                if (!in_array($data['status'], ['present', 'absent_non_justifie'])) {
                    return redirect()->back()->with('error', "Les formateurs ne peuvent émarger qu'en tant que 'Présent' ou 'Absent'.");
                }
            }

            $courseDate = \Carbon\Carbon::parse($date);

            // 2. Restriction: only allowed to take attendance for today
            if (!$courseDate->isToday()) {
                return redirect()->back()->with('error', "Les formateurs ne peuvent faire l'appel que pour la date du jour.");
            }

            // 3. Restriction: check if already validated/taken for this specific schedule
            $alreadyTaken = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->exists();

            if ($alreadyTaken) {
                return redirect()->back()->with('error', "L'émargement pour le créneau " . substr($schedule->start_time, 0, 5) . " - " . substr($schedule->end_time, 0, 5) . " a déjà été validé aujourd'hui et ne peut plus être modifié.");
            }

            // 4. Restriction: check timeframe with buffer
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->end_time)->setDateFrom($now)->addMinutes($bufferAfter);

            if (!$now->between($startTime, $endTime)) {
                $msg = sprintf("L'émargement n'est autorisé que durant le créneau du cours (tolérance: %dmin avant, %dmin après).", $bufferBefore, $bufferAfter);
                return redirect()->back()->with('error', $msg);
            }
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

