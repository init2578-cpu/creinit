<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRequest;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Module;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $trainers = User::role('Formateur')->with(['schedules' => function($q) {
            $q->whereHas('group', function($groupQ) {
                $groupQ->where('status', 'active');
            })->with('group:id,nom_groupe');
        }])->get(['id', 'name']);

        $assistants = User::role('Stagiaire')
            ->whereHas('internshipRecord', function($q) {
                $q->whereIn('internship_type', ['course_assistant', 'course_substitute']);
            })
            ->with(['schedules' => function($q) {
                $q->whereHas('group', function($groupQ) {
                    $groupQ->where('status', 'active');
                })->with('group:id,nom_groupe');
            }])
            ->get(['id', 'name'])
            ->map(function($user) {
                $user->name = $user->name . " (Assistant)";
                return $user;
            });
            
        $formateurs = $trainers->concat($assistants);

        $groups = Group::with(['module', 'formateur', 'students'])
            ->withCount('students')
            ->get()
            ->sortByDesc(function ($group) {
                if (preg_match('/^G(\d+)/i', (string) $group->nom_groupe, $matches)) {
                    return (int) $matches[1];
                }
                return $group->id;
            })
            ->values();

        return Inertia::render('Scolarite/GroupsIndex', [
            'groups' => $groups,
            'modules' => Module::activeForEnrollment()->get(['id', 'titre']),
            'formateurs' => $formateurs,
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Automatic naming logic: G{n}-{YY}
        $academicYear = $validated['annee_academique']; // e.g., 2024-2025
        $startYear = explode('-', $academicYear)[0];
        $yearSuffix = substr($startYear, -2);
        
        $maxGroup = Group::where('annee_academique', 'like', $startYear . '-%')
            ->get()
            ->map(function ($group) {
                if (preg_match('/^G(\d+)-/', $group->nom_groupe, $matches)) {
                    return (int)$matches[1];
                }
                return 0;
            })
            ->max();
        
        $nextNumber = ($maxGroup ?: 0) + 1;
        
        $validated['nom_groupe'] = "G{$nextNumber}-{$yearSuffix}";

        Group::create($validated);

        return back()->with('success', 'Le groupe de formation a été créé avec succès.');
    }

    public function update(StoreGroupRequest $request, Group $group): RedirectResponse
    {
        $validated = $request->validated();

        // If a group leader is already assigned, only the Director can modify or remove them
        if ($group->responsable_groupe_id !== null && array_key_exists('responsable_groupe_id', $validated) && $validated['responsable_groupe_id'] !== $group->responsable_groupe_id) {
            if (!$request->user()->hasRole('Directeur')) {
                return back()->withErrors(['responsable_groupe_id' => 'Seul le Directeur est autorisé à modifier ou retirer un responsable de groupe existant.']);
            }
        }

        $oldResponsableId = $group->responsable_groupe_id;
        $oldAdjointId = $group->adjoint_groupe_id;
        $oldFormateurId = $group->formateur_id;

        $group->update($validated);

        if ($oldFormateurId !== $group->formateur_id) {
            \App\Models\Schedule::where('group_id', $group->id)->update(['formateur_id' => $group->formateur_id]);
        }

        // Handle Responsable change
        if ($oldResponsableId !== $group->responsable_groupe_id) {
            $this->notifyRoleChange($group, 'Chef de groupe', $oldResponsableId, $group->responsable_groupe_id);
            
            // Delete any pending nominations for this role now that it's manually filled
            \App\Models\Nomination::where('group_id', $group->id)
                ->where('role', 'responsable')
                ->where('status', 'pending')
                ->delete();

            // Assign Spatie role to new leader
            if ($group->responsable_groupe_id) {
                $newLeader = User::find($group->responsable_groupe_id);
                $newLeader->assignRole('Responsable Groupe');
                $newLeader->givePermissionTo('validate-chapters');
            }
        }

        // Handle Adjoint change
        if ($oldAdjointId !== $group->adjoint_groupe_id) {
            $this->notifyRoleChange($group, 'Adjoint', $oldAdjointId, $group->adjoint_groupe_id);

            // Delete any pending nominations for this role
            \App\Models\Nomination::where('group_id', $group->id)
                ->where('role', 'adjoint')
                ->where('status', 'pending')
                ->delete();
        }

        return back()->with('success', 'Le groupe a été mis à jour avec succès.');
    }

    private function notifyRoleChange(Group $group, string $roleLabel, $oldId, $newId): void
    {
        $trainer = $group->formateur;
        $groupName = $group->nom_groupe;

        // Notify Trainer
        if ($trainer) {
            $userName = $newId ? User::find($newId)->name : ($oldId ? User::find($oldId)->name : 'N/A');
            $action = $newId ? 'attribué' : 'retiré';
            $trainer->notify(new \App\Notifications\GroupRoleChangedNotification($groupName, $roleLabel, $action, $userName));
        }

        // Notify New Student
        if ($newId) {
            $newStudent = User::find($newId);
            $newStudent->notify(new \App\Notifications\GroupRoleChangedNotification($groupName, $roleLabel, 'attribué', 'vous'));

            // Notify all other students of this group
            $otherStudents = $group->students()
                ->where('users.id', '!=', $newId)
                ->where('users.id', '!=', $oldId)
                ->get();
            foreach ($otherStudents as $student) {
                $student->notify(new \App\Notifications\GroupRoleChangedNotification($groupName, $roleLabel, 'attribué', $newStudent->name));
            }
        }

        // Notify Old Student
        if ($oldId) {
            $oldStudent = User::find($oldId);
            $oldStudent->notify(new \App\Notifications\GroupRoleChangedNotification($groupName, $roleLabel, 'retiré', 'vous'));
        }
    }

    public function destroy(Group $group): RedirectResponse
    {
        // Safety check: Don't delete if it has students
        if ($group->students()->count() > 0) {
            return back()->withErrors(['group' => 'Impossible de supprimer un groupe contenant des apprenants.']);
        }

        // Safety check: Don't delete if it has attendance history
        if (Attendance::where('group_id', $group->id)->exists()) {
            return back()->withErrors(['group' => 'Impossible de supprimer un groupe possédant un historique d\'émargement. Veuillez plutôt le clôturer pour archiver ses données.']);
        }

        $group->delete();

        return back()->with('success', 'Le groupe a été supprimé avec succès.');
    }

    /**
     * Close (archive) a group when the training is done.
     * Sets status to 'closed'. Schedules and attendances are kept to preserve history.
     */
    public function close(Group $group): RedirectResponse
    {
        $group->update(['status' => 'closed']);

        return back()->with('success', "Le groupe « {$group->nom_groupe} » a été clôturé avec succès. L'historique d'émargement et les données du groupe sont archivés.");
    }

    /**
     * Reopen a previously closed group.
     */
    public function reopen(Group $group): RedirectResponse
    {
        $group->update(['status' => 'active']);

        return back()->with('success', "Le groupe « {$group->nom_groupe} » a été réactivé.");
    }

    /**
     * Display the full attendance history and summary for a group (active or closed).
     */
    public function attendanceHistory(Request $request, Group $group): Response
    {
        $user = $request->user();
        if (!$user->hasRole('Directeur') && !$user->hasRole('Secrétaire') && !$user->isTrainer()) {
            abort(403);
        }

        if ($user->isTrainer() && !$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            $trainerIds = [$user->id];
            if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
                $trainerIds[] = $user->internshipRecord->tuteur_id;
            }
            if (!in_array($group->formateur_id, $trainerIds)) {
                abort(403, "Vous n'avez pas accès à l'historique de ce groupe.");
            }
        }

        $group->load(['module', 'formateur', 'responsableGroupe', 'adjointGroupe', 'schedules.room']);

        $attendances = Attendance::with('user:id,name,email,telephone,profile_photo_path')
            ->where('group_id', $group->id)
            ->orderByDesc('date')
            ->get();

        // Group attendances by session (date + schedule_id)
        $sessions = $attendances->groupBy(function ($item) {
            return $item->date . '_' . ($item->schedule_id ?? 'nosched');
        })->map(function ($items) use ($group) {
            $first = $items->first();
            $schedule = $first->schedule_id 
                ? $group->schedules->firstWhere('id', $first->schedule_id) 
                : null;
            if (!$schedule && $first->schedule_id) {
                $schedule = Schedule::with(['room', 'formateur'])->find($first->schedule_id);
            }

            $trainerId = $schedule?->formateur_id ?? $group->formateur_id;
            $studentItems = $items->filter(fn($item) => $item->user_id !== $trainerId);
            $trainerRecord = $items->firstWhere('user_id', $trainerId);

            return [
                'date' => $first->date,
                'schedule_id' => $first->schedule_id,
                'schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room?->nom ?? 'N/A',
                    'formateur' => $schedule->formateur?->name ?? 'N/A',
                ] : null,
                'trainer_status' => $trainerRecord ? $trainerRecord->status : 'Non renseigné',
                'total_students' => $studentItems->count(),
                'present' => $studentItems->where('status', 'present')->count(),
                'absent_non_justifie' => $studentItems->where('status', 'absent_non_justifie')->count(),
                'justifie' => $studentItems->where('status', 'justifie')->count(),
                'late' => $studentItems->whereIn('status', ['late', 'en_retard'])->count(),
                'records' => $studentItems->map(fn($item) => [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name ?? 'Apprenant',
                    'status' => $item->status,
                ])->values(),
            ];
        })->values()->sortByDesc('date')->values();

        $students = $group->students()
            ->select('users.id', 'users.name', 'users.email', 'users.telephone', 'users.profile_photo_path')
            ->orderBy('users.name')
            ->get();

        $totalSessionsCount = $sessions->count();

        $studentsSummary = $students->map(function ($student) use ($group, $attendances, $totalSessionsCount) {
            $studentAttendances = $attendances->where('user_id', $student->id);

            $presences = $studentAttendances->where('status', 'present')->count();
            $absentNonJustifie = $studentAttendances->where('status', 'absent_non_justifie')->count();
            $justifie = $studentAttendances->where('status', 'justifie')->count();
            $late = $studentAttendances->whereIn('status', ['late', 'en_retard'])->count();
            $totalRecorded = $presences + $absentNonJustifie + $justifie + $late;

            $rate = $totalRecorded > 0 
                ? (float) round(($presences / $totalRecorded) * 100, 1) 
                : ($totalSessionsCount > 0 ? 0.0 : 100.0);

            $role = 'Apprenant';
            if ($student->id === $group->responsable_groupe_id) {
                $role = 'Chef de groupe';
            } elseif ($student->id === $group->adjoint_groupe_id) {
                $role = 'Adjoint';
            }

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'telephone' => $student->telephone,
                'profile_photo_url' => $student->profile_photo_url,
                'role' => $role,
                'total_sessions' => $totalSessionsCount,
                'presences_count' => $presences,
                'absences_count' => $absentNonJustifie + $justifie,
                'absences_non_justifiees_count' => $absentNonJustifie,
                'absences_justifiees_count' => $justifie,
                'late_count' => $late,
                'attendance_rate' => $rate,
            ];
        });

        $totalPresences = (int) $studentsSummary->sum('presences_count');
        $totalAbsences = (int) $studentsSummary->sum('absences_count');
        $totalLates = (int) $studentsSummary->sum('late_count');
        $avgRate = $studentsSummary->count() > 0 
            ? (float) round($studentsSummary->avg('attendance_rate'), 1) 
            : 100.0;

        return Inertia::render('Scolarite/GroupAttendanceHistory', [
            'group' => $group,
            'stats' => [
                'total_sessions' => $totalSessionsCount,
                'overall_attendance_rate' => $avgRate,
                'total_presences' => $totalPresences,
                'total_absences' => $totalAbsences,
                'total_lates' => $totalLates,
            ],
            'students' => $studentsSummary,
            'sessions' => $sessions,
        ]);
    }
}
