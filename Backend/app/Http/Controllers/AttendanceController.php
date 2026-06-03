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
        $groups = Group::with('module')
            ->where('formateur_id', $request->user()->id)
            ->get();

        return Inertia::render('Attendances/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the attendance form for a specific group.
     */
    public function takeAttendance(Group $group): Response
    {
        $group->load('students');

        return Inertia::render('Attendances/TakeAttendance', [
            'group'    => $group,
            'students' => $group->students,
        ]);
    }

    /**
     * Store multiple attendance records (Bulk).
     * Protected by EnsureWithinPremises middleware.
     */
    public function storeBatch(StoreBatchAttendanceRequest $request): RedirectResponse
    {
        foreach ($request->validated('attendances') as $data) {
            Attendance::updateOrCreate(
                [
                    'user_id'  => $data['user_id'],
                    'group_id' => $request->validated('group_id'),
                    'date'     => $request->validated('date'),
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

        $group = Group::find($request->validated('group_id'));
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

