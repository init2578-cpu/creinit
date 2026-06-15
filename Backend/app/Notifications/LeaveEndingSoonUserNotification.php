<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Leave;

class LeaveEndingSoonUserNotification extends Notification implements ShouldQueue
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
            ->subject('Rappel : Fin de congé imminente')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous vous rappelons que votre congé (du {$this->leave->date_debut->format('d/m/Y')} au {$this->leave->date_fin->format('d/m/Y')}) prend fin dans exactement 3 jours.")
            ->line("Nous espérons que vous avez passé un excellent moment.")
            ->action('Voir mes congés', url('/leaves'))
            ->line('À très bientôt !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_ending_soon',
            'title' => 'Fin de congé imminente',
            'message' => "Votre congé prend fin le {$this->leave->date_fin->format('d/m/Y')} (dans 3 jours).",
            'leave_id' => $this->leave->id,
            'action_url' => '/leaves',
        ];
    }
}
