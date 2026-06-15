<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Leave;

class LeaveStatusUpdatedNotification extends Notification implements ShouldQueue
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
        $statusText = $this->leave->status === 'approuve' ? 'approuvée' : 'rejetée';
        $color = $this->leave->status === 'approuve' ? 'success' : 'error';

        $message = (new MailMessage)
            ->subject("Mise à jour de votre demande de congé")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre demande de congé du {$this->leave->date_debut->format('d/m/Y')} au {$this->leave->date_fin->format('d/m/Y')} a été **{$statusText}**.");

        if ($this->leave->admin_commentaire) {
            $message->line("Commentaire de l'administration : {$this->leave->admin_commentaire}");
        }

        return $message
            ->action('Voir mes congés', url('/leaves'))
            ->line('Merci de votre confiance.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusText = $this->leave->status === 'approuve' ? 'approuvée' : 'rejetée';
        
        return [
            'type' => 'leave_status',
            'title' => "Demande de congé {$statusText}",
            'message' => "Votre demande du {$this->leave->date_debut->format('d/m/Y')} a été {$statusText}.",
            'leave_id' => $this->leave->id,
            'status' => $this->leave->status,
        ];
    }
}
