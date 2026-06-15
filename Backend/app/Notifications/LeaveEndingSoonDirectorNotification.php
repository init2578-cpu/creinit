<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Leave;

class LeaveEndingSoonDirectorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $leave;

    /**
     * Create a new notification instance.
     */
    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_ending_soon_director',
            'title' => 'Fin de congé collaborateur',
            'message' => "Le congé de {$this->leave->user->name} prend fin le {$this->leave->date_fin->format('d/m/Y')} (dans 3 jours).",
            'leave_id' => $this->leave->id,
            'action_url' => '/leaves',
        ];
    }
}
