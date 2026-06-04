<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Scolarite\StoreScheduleRequest;
use App\Models\Room;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
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

        return Inertia::render('Scolarite/Schedules', [
            'schedules'  => $query->get(),
            'rooms'      => Room::all(),
            'groups'     => \App\Models\Group::with('formateur:id,name')->get(['id', 'nom_groupe', 'formateur_id']),
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
