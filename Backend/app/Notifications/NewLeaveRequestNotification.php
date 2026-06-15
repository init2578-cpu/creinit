<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Leave;

class NewLeaveRequestNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle demande de congé : {$this->leave->user->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle demande de congé a été soumise par **{$this->leave->user->name}**.")
            ->line("Type : {$this->leave->type}")
            ->line("Période : Du {$this->leave->date_debut->format('d/m/Y')} au {$this->leave->date_fin->format('d/m/Y')}")
            ->line("Motif : {$this->leave->motif}")
            ->action('Traiter la demande', url('/leaves'))
            ->line('Merci de la traiter dans les plus brefs délais.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_leave_request',
            'title' => 'Nouvelle demande de congé',
            'message' => "{$this->leave->user->name} a soumis une demande de congé.",
            'leave_id' => $this->leave->id,
        ];
    }
}
