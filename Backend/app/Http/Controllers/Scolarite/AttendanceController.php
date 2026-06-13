<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of sessions for a given date.
     */
    public function index(Request $request): Response
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        // Get schedules for this day of week
        $schedules = Schedule::query()
            ->with(['group.module', 'room', 'formateur'])
            ->where(function($query) use ($dayOfWeek) {
                $query->where('day_of_week', (int) $dayOfWeek);
            })
            ->get();

        // For each schedule, check if attendance is already taken
        $schedules->each(function ($schedule) use ($date) {
            $schedule->attendance_taken = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->exists();
        });

        return Inertia::render('Scolarite/AttendanceIndex', [
            'schedules' => $schedules,
            'selectedDate' => $date,
        ]);
    }

    /**
     * Show the attendance take page for a specific session.
     */
    public function take(Schedule $schedule, string $date): Response
    {
        $group = $schedule->group;
        
        // Students in the group
        $students = $group->students()->get(['users.id', 'users.name', 'users.email']);
        
        // Trainer of the session
        $trainer = $schedule->formateur;

        // Existing records for this session
        $existingAttendance = Attendance::where('schedule_id', $schedule->id)
            ->where('date', $date)
            ->get(['user_id', 'status'])
            ->keyBy('user_id');

        $participants = $students->map(function ($student) use ($existingAttendance) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'status' => $existingAttendance->get($student->id)?->status ?? 'present',
                'is_trainer' => false,
            ];
        });

        if ($trainer) {
            $participants->prepend([
                'id' => $trainer->id,
                'name' => "[FORMATEUR] " . $trainer->name,
                'email' => $trainer->email,
                'status' => $existingAttendance->get($trainer->id)?->status ?? 'present',
                'is_trainer' => true,
            ]);

            $assistants = User::role('Stagiaire')
                ->whereHas('internshipRecord', function($q) use ($trainer) {
                    $q->where('internship_type', 'course_assistant')
                      ->where('tuteur_id', $trainer->id);
                })
                ->get(['id', 'name', 'email']);

            foreach ($assistants as $assistant) {
                $participants->prepend([
                    'id' => $assistant->id,
                    'name' => "[ASSISTANT] " . $assistant->name,
                    'email' => $assistant->email,
                    'status' => $existingAttendance->get($assistant->id)?->status ?? 'present',
                    'is_trainer' => true,
                ]);
            }
        }

        return Inertia::render('Scolarite/AttendanceTake', [
            'schedule' => $schedule->load(['group.module', 'formateur', 'room']),
            'date' => $date,
            'students' => $participants,
            'settings' => [
                'latitude' => Setting::getValue('cre_latitude'),
                'longitude' => Setting::getValue('cre_longitude'),
                'radius' => Setting::getValue('cre_radius'),
            ]
        ]);
    }

    /**
     * Save attendance for a session.
     */
    public function store(Request $request): RedirectResponse
    {
        $scheduleId = $request->input('schedule_id');
        $gpsRequired = true;
        if ($scheduleId) {
            $schedule = Schedule::find($scheduleId);
            if ($schedule && $schedule->group && !$schedule->group->gps_check_required) {
                $gpsRequired = false;
            }
        }

        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date',
            'latitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric'],
            'longitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric'],
            'students' => 'required|array',
            'students.*.id' => 'required|exists:users,id',
            'students.*.status' => 'required|string|in:present,absent_non_justifie,late,justifie',
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);
        $now = Carbon::now();
        $courseDate = Carbon::parse($validated['date']);

        // 1. Détermination du créneau autorisé
        $bufferBefore = (int) Setting::getValue('attendance_buffer_before', 10);
        $bufferAfter = (int) Setting::getValue('attendance_buffer_after', 15);

        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
        $endTime = Carbon::createFromFormat('H:i:s', $schedule->end_time)->setDateFrom($now)->addMinutes($bufferAfter);

        $isWithinTimeframe = $courseDate->isToday() && $now->between($startTime, $endTime);

        if (!$isWithinTimeframe) {
            $existingAttendances = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $validated['date'])
                ->get()
                ->keyBy('user_id');

            $unauthorizedChanges = false;
            foreach ($validated['students'] as $studentData) {
                $existing = $existingAttendances->get((int)$studentData['id']);
                // Le frontend initialise par défaut à 'present' les apprenants/formateurs sans statut.
                // On considère donc 'present' comme le statut initial si aucun enregistrement n'existe.
                $oldStatus = $existing ? $existing->status : 'present';
                $newStatus = $studentData['status'];

                // On autorise la modification uniquement si le nouveau statut est 'justifie'
                // ou si le statut n'a pas changé par rapport à l'existant (ou au défaut).
                if ($oldStatus !== $newStatus && $newStatus !== 'justifie') {
                    $unauthorizedChanges = true;
                    break;
                }
            }

            if ($unauthorizedChanges) {
                $msg = sprintf("L'émargement complet n'est autorisé que le jour même et durant le créneau (tolérance: %dmin avant, %dmin après). En dehors de ce délai, vous ne pouvez que modifier le statut d'un apprenant vers 'Justifié'.", $bufferBefore, $bufferAfter);
                return back()->withErrors(['schedule_id' => $msg]);
            }
        }

        foreach ($validated['students'] as $studentData) {
            Attendance::updateOrCreate(
                [
                    'user_id' => $studentData['id'],
                    'group_id' => $schedule->group_id,
                    'schedule_id' => $schedule->id,
                    'date' => $validated['date'],
                ],
                [
                    'status'    => $studentData['status'],
                    'latitude'  => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]
            );
        }

        // Clear dashboard cache to reflect new attendance data
        \Illuminate\Support\Facades\Cache::forget('director_dashboard_kpis');

        $group = Group::find($schedule->group_id);
        if ($group) {
            Group::checkQuotaAndNotify($group);
        }

        return redirect()->route('attendance.index', ['date' => $validated['date']])
            ->with('success', 'La liste de présence a été enregistrée avec succès.');
    }
}
