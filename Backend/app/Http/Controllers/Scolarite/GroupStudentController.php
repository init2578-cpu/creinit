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
            ->join('applications', function ($join) use ($group) {
                $join->on('users.id', '=', 'applications.user_id')
                     ->where('applications.module_id', '=', $group->module_id);
            })
            ->get(['users.id', 'users.name', 'users.email', 'users.telephone', 'users.profile_photo_path', 'applications.sexe']);

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
            ->get(['users.id', 'users.name', 'users.email', 'users.telephone', 'users.profile_photo_path', 'applications.sexe']);

        return Inertia::render('Scolarite/GroupStudents', [
            'group' => $group->load(['module', 'formateur']),
            'currentStudents' => $currentStudents,
            'availableStudents' => $availableStudents,
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
