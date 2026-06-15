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

        $message->action('Voir mes congés', url('/leaves'))
            ->line('Merci de votre confiance.');

        if ($this->leave->status === 'approuve') {
            $icsContent = $this->generateIcs();
            $message->attachData($icsContent, 'mon_conge.ics', [
                'mime' => 'text/calendar',
            ]);
        }

        return $message;
    }

    private function generateIcs(): string
    {
        $start = $this->leave->date_debut->format('Ymd');
        // iCal all-day end dates are exclusive, so we add 1 day
        $end = $this->leave->date_fin->copy()->addDay()->format('Ymd');
        $now = now()->timezone('UTC')->format('Ymd\THis\Z');
        $uid = uniqid() . '@e-cre.com';
        $type = $this->leave->type;

        return "BEGIN:VCALENDAR\r\n" .
               "VERSION:2.0\r\n" .
               "PRODID:-//E-CRE//NONSGML v1.0//EN\r\n" .
               "BEGIN:VEVENT\r\n" .
               "UID:{$uid}\r\n" .
               "DTSTAMP:{$now}\r\n" .
               "DTSTART;VALUE=DATE:{$start}\r\n" .
               "DTEND;VALUE=DATE:{$end}\r\n" .
               "SUMMARY:Congé ({$type})\r\n" .
               "DESCRIPTION:Congé approuvé via la plateforme E-CRE.\r\n" .
               "END:VEVENT\r\n" .
               "END:VCALENDAR";
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
