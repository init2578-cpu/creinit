<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GroupHoursExceededNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $groupName,
        public string $moduleTitle,
        public int $quotaHours,
        public float $completedHours
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $excess = round($this->completedHours - $this->quotaHours, 1);
        return [
            'type'    => 'group_hours_exceeded',
            'title'   => "Quota horaire dépassé !",
            'message' => "Le groupe {$this->groupName} a dépassé son quota horaire pour le module '{$this->moduleTitle}'. Quota : {$this->quotaHours}h, Réalisé : {$this->completedHours}h (+{$excess}h).",
            'group_name' => $this->groupName,
            'completed_hours' => $this->completedHours,
            'quota_hours' => $this->quotaHours,
        ];
    }
}
