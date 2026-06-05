<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nom_groupe',
        'module_id',
        'formateur_id',
        'responsable_groupe_id',
        'adjoint_groupe_id',
        'annee_academique',
        'gps_check_required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'module_id'             => 'integer',
            'formateur_id'          => 'integer',
            'responsable_groupe_id' => 'integer',
            'adjoint_groupe_id'     => 'integer',
            'gps_check_required'    => 'boolean',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /**
     * The module this group is trained on.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * The user acting as the trainer (formateur) for this group.
     */
    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    /**
     * The user acting as the group supervisor (responsable), optional.
     */
    public function responsableGroupe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_groupe_id');
    }

    /**
     * The user acting as the group deputy (adjoint), optional.
     */
    public function adjointGroupe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjoint_groupe_id');
    }

    /**
     * Nominations for group supervisor within this group.
     */
    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    /**
     * Chapter progress entries for this group.
     */
    public function chapterProgress(): HasMany
    {
        return $this->hasMany(ChapterGroupProgress::class);
    }

    /**
     * Schedules for this group.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Students (apprenants) assigned to this group.
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    /**
     * Alias for students() — used by some controllers.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->students();
    }

    /**
     * Check if the group has exceeded its module's hourly quota and notify formateurs & directors.
     */
    public static function checkQuotaAndNotify(Group $group): void
    {
        $group->load('module');
        if (!$group->module) {
            return;
        }
        $quotaHours = $group->module->quota_heures;

        // Calculate completed hours
        // 1. Sessions with schedule_id
        $scheduledSessions = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('group_id', $group->id)
            ->whereNotNull('schedule_id')
            ->select('date', 'schedule_id')
            ->distinct()
            ->get();

        $totalSeconds = 0;
        $scheduledDates = [];
        foreach ($scheduledSessions as $session) {
            $schedule = \App\Models\Schedule::find($session->schedule_id);
            if ($schedule) {
                $startTime = \Carbon\Carbon::parse($schedule->start_time);
                $endTime = \Carbon\Carbon::parse($schedule->end_time);
                $totalSeconds += $endTime->diffInSeconds($startTime);
                $scheduledDates[] = $session->date;
            }
        }

        // 2. Sessions without schedule_id (legacy or fallback)
        $unscheduledDates = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('group_id', $group->id)
            ->whereNull('schedule_id')
            ->whereNotIn('date', $scheduledDates)
            ->select('date')
            ->distinct()
            ->pluck('date');

        foreach ($unscheduledDates as $date) {
            $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeekIso;
            $schedule = \App\Models\Schedule::where('group_id', $group->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();

            if ($schedule) {
                $startTime = \Carbon\Carbon::parse($schedule->start_time);
                $endTime = \Carbon\Carbon::parse($schedule->end_time);
                $totalSeconds += $endTime->diffInSeconds($startTime);
            } else {
                $totalSeconds += 7200; // Default: 2 hours
            }
        }

        $completedHours = $totalSeconds / 3600;

        if ($completedHours > $quotaHours) {
            $notification = new \App\Notifications\GroupHoursExceededNotification(
                $group->nom_groupe,
                $group->module->titre,
                $quotaHours,
                $completedHours
            );

            // Notify formateur
            $formateur = \App\Models\User::find($group->formateur_id);
            if ($formateur) {
                $alreadyNotified = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('notifiable_id', $formateur->id)
                    ->where('data', 'like', '%"type":"group_hours_exceeded"%')
                    ->where('data', 'like', '%"group_name":"' . addslashes($group->nom_groupe) . '"%')
                    ->exists();

                if (!$alreadyNotified) {
                    $formateur->notify($notification);

                    // Notify all users with 'Directeur' role
                    $directeurs = \App\Models\User::role('Directeur')->get();
                    foreach ($directeurs as $directeur) {
                        $directeur->notify($notification);
                    }
                }
            }
        }
    }
}
