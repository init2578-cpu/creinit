<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Nomination\ApproveNominationRequest;
use App\Http\Requests\Nomination\StoreNominationRequest;
use App\Models\Nomination;
use App\Models\User;
use App\Notifications\NominationProposedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NominationController extends Controller
{
    /**
     * Display pending nominations (for Secrétaire).
     */
    public function index(): Response
    {
        $nominations = Nomination::with(['group.module', 'user', 'nominator'])
            ->pending()
            ->latest()
            ->paginate(15);

        return Inertia::render('Nominations/Index', [
            'nominations' => $nominations,
        ]);
    }

    /**
     * Store a new nomination (Formateur proposes an Apprenant).
     */
    public function store(StoreNominationRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $trainer */
        $trainer = $request->user();

        // One slot, one candidate: any existing pending nomination for this ROLE in this GROUP is superseded.
        Nomination::where('group_id', $request->validated('group_id'))
            ->where('role', $request->validated('role', 'responsable'))
            ->where('status', 'pending')
            ->delete();

        $nomination = Nomination::create([
            'group_id'     => $request->validated('group_id'),
            'user_id'      => $request->validated('user_id'),
            'role'         => $request->validated('role', 'responsable'),
            'nominated_by' => $trainer->id,
            'status'       => 'pending',
        ]);

        $nomination->load(['group', 'user', 'nominator']);

        // Notify all users with role Secrétaire
        $secretaires = User::role('Secrétaire')->get();
        foreach ($secretaires as $secretaire) {
            $secretaire->notify(new NominationProposedNotification($nomination));
        }

        return redirect()
            ->back()
            ->with('success', 'Nomination soumise avec succès. Le secrétariat a été notifié.');
    }

    /**
     * Approve or reject a nomination (Secrétaire).
     */
    public function approve(ApproveNominationRequest $request, Nomination $nomination): RedirectResponse
    {
        if ($nomination->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Cette nomination a déjà été traitée.');
        }

        /** @var \App\Models\User $secretary */
        $secretary = $request->user();
        $decision  = $request->validated('decision');

        DB::transaction(function () use ($nomination, $secretary, $decision): void {
            $nomination->update([
                'status'       => $decision,
                'validated_by' => $secretary->id,
                'validated_at' => now(),
            ]);

            if ($decision === 'approved') {
                $nominee = $nomination->user;
                $group   = $nomination->group;

                if ($nomination->role === 'responsable') {
                    // Assign role + permission
                    $nominee->assignRole('Responsable Groupe');
                    $nominee->givePermissionTo('validate-chapters');

                    // Update group table directly via relationship query
                    $nomination->group()->update(['responsable_groupe_id' => $nominee->id]);
                    
                    // If they were adjoint, remove them
                    if ($nomination->group->adjoint_groupe_id === $nominee->id) {
                        $nomination->group()->update(['adjoint_groupe_id' => null]);
                    }
                } else {
                    // Update group table directly via relationship query
                    $nomination->group()->update(['adjoint_groupe_id' => $nominee->id]);

                    // If they were responsable, remove them
                    if ($nomination->group->responsable_groupe_id === $nominee->id) {
                        $nomination->group()->update(['responsable_groupe_id' => null]);
                    }
                }
            }
        });

        $message = $decision === 'approved'
            ? 'Nomination approuvée. Le rôle a été attribué.'
            : 'Nomination rejetée.';

        // Dispatch notifications outside of the transaction block
        $group     = $nomination->group;
        $nominee   = $nomination->user;
        $nominator = $nomination->nominator;
        $roleLabel = $nomination->role === 'responsable' ? 'Responsable' : 'Adjoint';

        if ($decision === 'approved') {
            // 1. Notify the Trainer (nominator)
            if ($nominator) {
                $nominator->notify(new \App\Notifications\GroupRoleChangedNotification(
                    $group->nom_groupe,
                    $roleLabel,
                    'approuvé pour',
                    $nominee->name
                ));
            }

            // 2. Notify the Nominee (student receiving role)
            $nominee->notify(new \App\Notifications\GroupRoleChangedNotification(
                $group->nom_groupe,
                $roleLabel,
                'attribué',
                'vous'
            ));

            // 3. Notify all other students of this group
            $otherStudents = $group->students()
                ->where('users.id', '!=', $nominee->id)
                ->get();
            foreach ($otherStudents as $student) {
                $student->notify(new \App\Notifications\GroupRoleChangedNotification(
                    $group->nom_groupe,
                    $roleLabel,
                    'attribué',
                    $nominee->name
                ));
            }
        } else {
            // If rejected, notify the Trainer (nominator)
            if ($nominator) {
                $nominator->notify(new \App\Notifications\GroupRoleChangedNotification(
                    $group->nom_groupe,
                    $roleLabel,
                    'rejeté pour',
                    $nominee->name
                ));
            }
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
