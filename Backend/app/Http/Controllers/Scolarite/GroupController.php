<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $trainers = User::role('Formateur')->get(['id', 'name']);
        $assistants = User::role('Stagiaire')
            ->whereHas('internshipRecord', function($q) {
                $q->where('internship_type', 'course_assistant');
            })
            ->get(['id', 'name'])
            ->map(function($user) {
                $user->name = $user->name . " (Assistant)";
                return $user;
            });
        $formateurs = $trainers->concat($assistants);

        return Inertia::render('Scolarite/GroupsIndex', [
            'groups' => Group::with(['module', 'formateur', 'students'])
                ->withCount('students')
                ->orderByDesc('created_at')
                ->get(),
            'modules' => Module::all(['id', 'titre']),
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
        
        $count = Group::where('annee_academique', 'like', $startYear . '-%')->count();
        $nextNumber = $count + 1;
        
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

        $group->update($validated);

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
        // Safety check: Don't delete if it has students? 
        // Or let it delete and cascade if the DB is set up that way.
        if ($group->students()->count() > 0) {
            return back()->withErrors(['group' => 'Impossible de supprimer un groupe contenant des apprenants.']);
        }

        $group->delete();

        return back()->with('success', 'Le groupe a été supprimé avec succès.');
    }

    /**
     * Close (archive) a group when the training is done.
     * Sets status to 'closed' and removes all schedules.
     */
    public function close(Group $group): RedirectResponse
    {
        // Delete all associated schedules
        $group->schedules()->delete();

        $group->update(['status' => 'closed']);

        return back()->with('success', "Le groupe « {$group->nom_groupe} » a été clôturé. Tous ses créneaux ont été supprimés.");
    }

    /**
     * Reopen a previously closed group.
     */
    public function reopen(Group $group): RedirectResponse
    {
        $group->update(['status' => 'active']);

        return back()->with('success', "Le groupe « {$group->nom_groupe} » a été réactivé.");
    }
}
