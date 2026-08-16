<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Scolarite\StoreScheduleRequest;
use App\Models\Attendance;
use App\Models\Room;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    /**
     * Get schedules for today where roll call has NOT been taken 15+ minutes after start time.
     */
    public function pendingAlerts(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            if (!$user || (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire') && !$user->hasRole('Admin'))) {
                return response()->json(['alerts' => [], 'count' => 0]);
            }

            $now = Carbon::now();
            $currentDay = (int) $now->dayOfWeekIso;
            $todayStr = $now->toDateString();

            $schedules = Schedule::query()
                ->where('day_of_week', $currentDay)
                ->whereHas('group', fn($q) => $q->where('status', 'active'))
                ->with(['group', 'room', 'formateur'])
                ->get();

            $alerts = [];

            foreach ($schedules as $schedule) {
                if (!$schedule->start_time || !$schedule->end_time) {
                    continue;
                }

                try {
                    $startTime = Carbon::parse($todayStr . ' ' . (string) $schedule->start_time);
                    $endTime   = Carbon::parse($todayStr . ' ' . (string) $schedule->end_time);

                    // Trigger alert if course started >= 15 min ago and course has not ended yet
                    if ($now->greaterThanOrEqualTo($startTime->copy()->addMinutes(15)) && $now->lessThan($endTime)) {
                        $attendanceTaken = Attendance::where('schedule_id', $schedule->id)
                            ->where('date', $todayStr)
                            ->exists();

                        if (!$attendanceTaken) {
                            $minutesLate = (int) $now->diffInMinutes($startTime);
                            $alerts[] = [
                                'schedule_id'  => $schedule->id,
                                'group_id'     => $schedule->group_id,
                                'group_name'   => $schedule->group->nom_groupe ?? 'Groupe',
                                'formateur'    => $schedule->formateur->name ?? 'Formateur non assigné',
                                'room'         => $schedule->room->nom ?? 'Salle non assignée',
                                'start_time'   => $startTime->format('H:i'),
                                'end_time'     => $endTime->format('H:i'),
                                'minutes_late' => $minutesLate,
                            ];
                        }
                    }
                } catch (\Throwable $itemError) {
                    continue;
                }
            }

            return response()->json([
                'alerts' => $alerts,
                'count'  => count($alerts),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Pending alerts error: ' . $e->getMessage());
            return response()->json(['alerts' => [], 'count' => 0]);
        }
    }

    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $query = Schedule::query()->with(['group', 'room', 'formateur']);

        // Trainers only see their own schedule. Directors/Secretaries see everything.
        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire') && $user->isTrainer()) {
            $trainerIds = [$user->id];
            if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
                $trainerIds[] = $user->internshipRecord->tuteur_id;
            }
            $query->whereIn('formateur_id', $trainerIds);
        }

        $trainers = \App\Models\User::role('Formateur')->get(['id', 'name']);
        $assistants = \App\Models\User::role('Stagiaire')
            ->whereHas('internshipRecord', function($q) {
                $q->where('internship_type', 'course_assistant');
            })
            ->get(['id', 'name'])
            ->map(function($user) {
                $user->name = $user->name . " (Assistant)";
                return $user;
            });
        $formateurs = $trainers->concat($assistants);

        $today = \Carbon\Carbon::today()->toDateString();
        $schedules = $query
            ->whereHas('group', fn($q) => $q->where('status', 'active'))
            ->get()
            ->map(function ($schedule) use ($today) {
                $schedule->attendance_taken_today = \App\Models\Attendance::where('schedule_id', $schedule->id)
                    ->where('date', $today)
                    ->exists();
                return $schedule;
            });

        return Inertia::render('Scolarite/Schedules', [
            'schedules'  => $schedules,
            'rooms'      => Room::all(),
            'groups'     => \App\Models\Group::with('formateur:id,name')
                ->where('status', 'active')
                ->get(['id', 'nom_groupe', 'formateur_id']),
            'formateurs' => $formateurs,
        ]);
    }


    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        Schedule::create($request->validated());

        return back()->with('success', 'Créneau d\'emploi du temps ajouté avec succès.');
    }

    public function update(StoreScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $schedule->update($request->validated());

        return back()->with('success', 'Créneau d\'emploi du temps mis à jour avec succès.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Créneau supprimé.');
    }
}
