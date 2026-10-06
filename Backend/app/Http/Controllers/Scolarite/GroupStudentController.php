<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupStudentController extends Controller
{
    public function index(Group $group): Response
    {
        // Students already in the group
        $currentStudents = $group->students()
            ->leftJoin('applications', function ($join) use ($group) {
                $join->on('users.id', '=', 'applications.user_id')
                     ->where('applications.module_id', '=', $group->module_id);
            })
            ->get([
                'users.id', 
                'users.name', 
                'users.email', 
                'users.telephone', 
                'users.profile_photo_path', 
                'applications.sexe',
                'applications.cni_path',
                'applications.diploma_path'
            ]);

        // Available users: admitted for the same module but not in THIS group
        $availableStudents = User::role(['Apprenant', 'Stagiaire'])
            ->join('applications', function ($join) use ($group) {
                $join->on('users.id', '=', 'applications.user_id')
                     ->where('applications.module_id', '=', $group->module_id)
                     ->where('applications.status', '=', 'admitted');
            })
            ->whereDoesntHave('studentGroups', function ($query) use ($group) {
                $query->where('group_id', $group->id);
            })
            ->get([
                'users.id', 
                'users.name', 
                'users.email', 
                'users.telephone', 
                'users.profile_photo_path', 
                'applications.sexe',
                'applications.cni_path',
                'applications.diploma_path'
            ]);

        // Fetch attendances for this group to calculate presence/absence per learner
        $attendances = \App\Models\Attendance::where('group_id', $group->id)->get();
        $totalSessions = $attendances->groupBy(function($item) {
            return $item->date . '_' . ($item->schedule_id ?? 'default');
        })->count();

        $currentStudents = $currentStudents->map(function ($student) use ($attendances, $totalSessions) {
            $userAttendances = $attendances->where('user_id', $student->id);
            $presences = $userAttendances->where('status', 'present')->count();
            $absentNonJustifie = $userAttendances->where('status', 'absent_non_justifie')->count();
            $justifie = $userAttendances->where('status', 'justifie')->count();
            $late = $userAttendances->whereIn('status', ['late', 'en_retard'])->count();
            $totalRecorded = $presences + $absentNonJustifie + $justifie + $late;

            $student->presences_count = $presences;
            $student->absences_count = $absentNonJustifie + $justifie;
            $student->absences_non_justifiees_count = $absentNonJustifie;
            $student->absences_justifiees_count = $justifie;
            $student->late_count = $late;
            $student->attendance_rate = $totalRecorded > 0
                ? (float) round(($presences / $totalRecorded) * 100, 1)
                : ($totalSessions > 0 ? 0.0 : 100.0);

            return $student;
        });

        $attendanceStats = [
            'total_sessions' => $totalSessions,
            'total_presences' => (int) $currentStudents->sum('presences_count'),
            'total_absences' => (int) $currentStudents->sum('absences_count'),
            'average_rate' => $currentStudents->count() > 0 
                ? (float) round($currentStudents->avg('attendance_rate'), 1) 
                : 100.0,
        ];

        return Inertia::render('Scolarite/GroupStudents', [
            'group' => $group->load(['module', 'formateur']),
            'currentStudents' => $currentStudents,
            'availableStudents' => $availableStudents,
            'attendanceStats' => $attendanceStats,
        ]);
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        if ($group->status === 'closed') {
            return back()->withErrors(['group' => 'Ce groupe est clôturé. Impossible d\'ajouter des apprenants.']);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group->students()->syncWithoutDetaching($validated['user_ids']);

        return back()->with('success', count($validated['user_ids']) . ' apprenant(s) ajouté(s) au groupe.');
    }

    public function destroy(Group $group, User $student): RedirectResponse
    {
        if ($group->status === 'closed') {
            return back()->withErrors(['group' => 'Ce groupe est clôturé. Impossible de retirer un apprenant.']);
        }

        $group->students()->detach($student->id);

        return back()->with('success', "L'apprenant a été retiré du groupe.");
    }
}
