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

        // Get schedules for this day of week (active groups with active schedule OR any schedule that had attendance taken on this date)
        $schedules = Schedule::withTrashed()
            ->with(['group.module', 'room', 'formateur'])
            ->where('day_of_week', (int) $dayOfWeek)
            ->where(function ($query) use ($date) {
                $query->where(function ($activeQ) {
                    $activeQ->whereNull('schedules.deleted_at')
                        ->whereHas('group', fn($q) => $q->where('status', 'active'));
                })
                ->orWhereExists(function ($sub) use ($date) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('attendances')
                        ->whereColumn('attendances.schedule_id', 'schedules.id')
                        ->where('attendances.date', $date);
                });
            })
            ->get();

        // For each schedule, check if attendance is already taken (excluding purely advance reported absences)
        $schedules->each(function ($schedule) use ($date) {
            $schedule->attendance_taken = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->where('is_advance_reported', false)
                ->exists();

            $schedule->advance_reported_count = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->where('is_advance_reported', true)
                ->count();
        });

        $user = auth()->user();
        $canReportAdvance = $user && ($user->hasRole('Directeur') || $user->hasRole('Secrétaire'));

        $activeGroups = [];
        if ($canReportAdvance) {
            $activeGroups = Group::where('status', 'active')
                ->with([
                    'students:users.id,users.name,users.email',
                    'schedules' => function ($q) {
                        $q->whereNull('deleted_at')->with(['room:id,nom', 'formateur:id,name']);
                    }
                ])
                ->get(['id', 'nom_groupe', 'module_id']);
        }

        return Inertia::render('Scolarite/AttendanceIndex', [
            'schedules' => $schedules,
            'selectedDate' => $date,
            'can_report_advance' => $canReportAdvance,
            'active_groups' => $activeGroups,
        ]);
    }

    /**
     * Show the attendance take page for a specific session.
     */
    public function take(Schedule $schedule, string $date): Response|RedirectResponse
    {
        $user = auth()->user();
        $isTrainer = !$user->hasRole('Directeur') && !$user->hasRole('Secrétaire');

        $alreadyTaken = Attendance::where('schedule_id', $schedule->id)
            ->where('date', $date)
            ->where('is_advance_reported', false)
            ->exists();

        $group = $schedule->group;
        $isClosedGroup = $group && $group->status === 'closed';

        // Trainers cannot edit a validated session, and closed groups are strictly readonly
        $readonly = ($isTrainer && $alreadyTaken) || $isClosedGroup;

        $group = $schedule->group;
        
        // Students in the group
        $students = $group->students()->get(['users.id', 'users.name', 'users.email']);
        
        // Trainer of the session
        $trainer = $schedule->formateur;

        // Existing records for this session
        $existingAttendance = Attendance::where('schedule_id', $schedule->id)
            ->where('date', $date)
            ->with('reportedByUser:id,name')
            ->get(['id', 'user_id', 'status', 'is_advance_reported', 'motif', 'reported_by', 'reported_at'])
            ->keyBy('user_id');

        $participants = $students->map(function ($student) use ($existingAttendance) {
            $record = $existingAttendance->get($student->id);
            return [
                'id'                  => $student->id,
                'name'                => $student->name,
                'email'               => $student->email,
                'status'              => $record?->status ?? 'present',
                'is_advance_reported' => (bool) ($record?->is_advance_reported ?? false),
                'motif'               => $record?->motif ?? null,
                'reported_at'         => $record?->reported_at?->format('d/m/Y H:i') ?? null,
                'reported_by_name'    => $record?->reportedByUser?->name ?? null,
                'is_trainer'          => false,
            ];
        });

        if ($trainer) {
            $record = $existingAttendance->get($trainer->id);
            $participants->prepend([
                'id'                  => $trainer->id,
                'name'                => "[FORMATEUR] " . $trainer->name,
                'email'               => $trainer->email,
                'status'              => $record?->status ?? 'present',
                'is_advance_reported' => false,
                'motif'               => null,
                'reported_at'         => null,
                'reported_by_name'    => null,
                'is_trainer'          => true,
            ]);

            $assistants = User::role('Stagiaire')
                ->whereHas('internshipRecord', function($q) use ($trainer) {
                    $q->where('internship_type', 'course_assistant')
                      ->where('tuteur_id', $trainer->id);
                })
                ->get(['id', 'name', 'email']);

            foreach ($assistants as $assistant) {
                $asstRecord = $existingAttendance->get($assistant->id);
                $participants->prepend([
                    'id'                  => $assistant->id,
                    'name'                => "[ASSISTANT] " . $assistant->name,
                    'email'               => $assistant->email,
                    'status'              => $asstRecord?->status ?? 'present',
                    'is_advance_reported' => false,
                    'motif'               => null,
                    'reported_at'         => null,
                    'reported_by_name'    => null,
                    'is_trainer'          => true,
                ]);
            }
        }

        return Inertia::render('Scolarite/AttendanceTake', [
            'schedule' => $schedule->load(['group.module', 'formateur', 'room']),
            'date' => $date,
            'students' => $participants,
            'readonly' => $readonly,
            'can_report_advance' => $user->hasRole('Directeur') || $user->hasRole('Secrétaire'),
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
        $user = auth()->user();
        $isTrainer = !$user->hasRole('Directeur') && !$user->hasRole('Secrétaire');

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

        if ($schedule->group && $schedule->group->status === 'closed' && $isTrainer) {
            return back()->withErrors(['schedule_id' => "Ce groupe est clôturé. La feuille d'émargement est archivée et ne peut plus être modifiée."]);
        }

        // Block trainers from modifying an already validated attendance sheet
        if ($isTrainer) {
            $alreadyTaken = Attendance::where('schedule_id', $schedule->id)
                ->where('date', $validated['date'])
                ->where('is_advance_reported', false)
                ->exists();

            if ($alreadyTaken) {
                return back()->withErrors(['schedule_id' => "L'émargement pour ce créneau a déjà été validé et ne peut plus être modifié."]);
            }
        }

        $now = Carbon::now();
        $courseDate = Carbon::parse($validated['date']);

        // 1. Détermination du créneau autorisé
        $bufferBefore = (int) Setting::getValue('attendance_buffer_before', 10);
        $bufferAfter = (int) Setting::getValue('attendance_buffer_after', 15);

        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time)->setDateFrom($now)->subMinutes($bufferBefore);
        $endTime = $startTime->copy()->addMinutes($bufferAfter);

        $isWithinTimeframe = $courseDate->isToday() && $now->between($startTime, $endTime);

        if ($isTrainer && !$isWithinTimeframe) {
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
                $msg = sprintf("L'émargement complet n'est autorisé que durant la fenêtre d'ouverture (de %s à %s, soit %d min après l'ouverture). En dehors de ce délai, vous ne pouvez que modifier le statut d'un apprenant vers 'Justifié'.", $startTime->format('H:i'), $endTime->format('H:i'), $bufferAfter);
                return back()->withErrors(['schedule_id' => $msg]);
            }
        }

        $existingRecords = Attendance::where('schedule_id', $schedule->id)
            ->where('date', $validated['date'])
            ->get()
            ->keyBy('user_id');

        foreach ($validated['students'] as $studentData) {
            $existing = $existingRecords->get((int)$studentData['id']);
            $isAdvance = false;
            $motif = null;
            $reportedBy = null;
            $reportedAt = null;

            if ($existing && $existing->is_advance_reported) {
                if ($studentData['status'] === 'justifie') {
                    $isAdvance = true;
                    $motif = $existing->motif;
                    $reportedBy = $existing->reported_by;
                    $reportedAt = $existing->reported_at;
                }
            }

            Attendance::updateOrCreate(
                [
                    'user_id' => $studentData['id'],
                    'group_id' => $schedule->group_id,
                    'schedule_id' => $schedule->id,
                    'date' => $validated['date'],
                ],
                [
                    'status'              => $studentData['status'],
                    'latitude'            => $validated['latitude'] ?? null,
                    'longitude'           => $validated['longitude'] ?? null,
                    'is_advance_reported' => $isAdvance,
                    'motif'               => $motif,
                    'reported_by'         => $reportedBy,
                    'reported_at'         => $reportedAt,
                ]
            );
        }

        // Clear dashboard cache to reflect new attendance data
        \Illuminate\Support\Facades\Cache::forget('director_dashboard_kpis');

        $group = Group::find($schedule->group_id);
        if ($group) {
            Group::checkQuotaAndNotify($group);
        }

        return redirect()->route('attendance.history', ['schedule' => $schedule->id])
            ->with('success', 'La liste de présence a été enregistrée avec succès.');
    }

    /**
     * Mention an advance absence reported by a student before the class.
     * Accessible only by Directeur and Secrétaire.
     */
    public function reportAdvanceAbsence(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire'))) {
            abort(403, "Seuls le Directeur et la Secrétaire peuvent mentionner les absences signalées par les apprenants.");
        }

        $validated = $request->validate([
            'schedule_id' => 'nullable',
            'group_id'    => 'required|exists:groups,id',
            'user_id'     => 'required|exists:users,id',
            'date'        => 'required|date',
            'motif'       => 'nullable|string|max:255',
            'action'      => 'nullable|string|in:report,cancel',
        ]);

        $group = Group::findOrFail($validated['group_id']);
        $student = User::findOrFail($validated['user_id']);
        $date = $validated['date'];
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso;

        // Resolve target schedules
        $targetScheduleIds = [];
        if (!empty($validated['schedule_id']) && $validated['schedule_id'] !== 'all') {
            $targetScheduleIds = [(int) $validated['schedule_id']];
        } else {
            $targetScheduleIds = Schedule::where('group_id', $group->id)
                ->where('day_of_week', $dayOfWeek)
                ->pluck('id')
                ->toArray();
            
            // If no schedule matches this day_of_week, pick any active schedule of group
            if (empty($targetScheduleIds)) {
                $targetScheduleIds = Schedule::where('group_id', $group->id)->pluck('id')->toArray();
            }
        }

        if (empty($targetScheduleIds)) {
            return back()->withErrors(['schedule_id' => "Aucun créneau d'emploi du temps trouvé pour ce groupe à cette date."]);
        }

        if (($validated['action'] ?? 'report') === 'cancel') {
            Attendance::whereIn('schedule_id', $targetScheduleIds)
                ->where('user_id', $student->id)
                ->where('date', $date)
                ->where('is_advance_reported', true)
                ->delete();

            \Illuminate\Support\Facades\Cache::forget('director_dashboard_kpis');

            return back()->with('success', "L'absence signalée pour {$student->name} a été retirée.");
        }

        foreach ($targetScheduleIds as $schedId) {
            Attendance::updateOrCreate(
                [
                    'user_id'     => $student->id,
                    'schedule_id' => $schedId,
                    'group_id'    => $group->id,
                    'date'        => $date,
                ],
                [
                    'status'              => 'justifie',
                    'is_advance_reported' => true,
                    'motif'               => !empty($validated['motif']) ? $validated['motif'] : 'Absence signalée avant le cours',
                    'reported_by'         => $user->id,
                    'reported_at'         => Carbon::now(),
                ]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('director_dashboard_kpis');

        return back()->with('success', "L'absence signalée pour {$student->name} a été enregistrée avec succès.");
    }

    /**
     * Display all attendance lists for a specific schedule.
     */
    public function history(Schedule $schedule): Response
    {
        $schedule->load(['group.module', 'formateur', 'room']);
        
        // Fetch all attendance records for this schedule
        $attendances = Attendance::where('schedule_id', $schedule->id)
            ->get();

        // Group by date
        $grouped = $attendances->groupBy('date')->map(function ($items, $date) use ($schedule) {
            $trainerId = $schedule->formateur_id;
            
            $studentItems = $items->filter(function ($item) use ($trainerId) {
                return $item->user_id !== $trainerId;
            });

            $total = $studentItems->count();
            $present = $studentItems->where('status', 'present')->count();
            $absent = $studentItems->where('status', 'absent_non_justifie')->count();
            $late = $studentItems->where('status', 'late')->count();
            $justified = $studentItems->where('status', 'justifie')->count();
            $advanceReported = $studentItems->where('is_advance_reported', true)->count();
            $isValidated = $items->where('is_advance_reported', false)->isNotEmpty();

            $trainerRecord = $items->firstWhere('user_id', $trainerId);
            $trainerStatus = $trainerRecord ? $trainerRecord->status : 'Non émargé';

            return [
                'date'             => $date,
                'total_students'   => $total,
                'present'          => $present,
                'absent'           => $absent,
                'late'             => $late,
                'justified'        => $justified,
                'advance_reported' => $advanceReported,
                'is_validated'     => $isValidated,
                'trainer_status'   => $trainerStatus,
            ];
        })->values()->sortByDesc('date')->values();

        return Inertia::render('Scolarite/AttendanceHistory', [
            'schedule' => $schedule,
            'history' => $grouped,
        ]);
    }
}
