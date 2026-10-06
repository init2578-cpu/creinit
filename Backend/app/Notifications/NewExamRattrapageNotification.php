<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamRattrapage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewExamRattrapageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ExamRattrapage $rattrapage,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->rattrapage->exam;
        $scheduledStr = $this->rattrapage->scheduled_at 
            ? $this->rattrapage->scheduled_at->format('d/m/Y à H:i') 
            : 'immédiat';

        return (new MailMessage())
            ->subject('Session de rattrapage accordée : ' . $exam->titre)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line("Suite à votre absence justifiée, une session de rattrapage a été planifiée pour l'examen **\"{$exam->titre}\"** ({$exam->module->titre}).")
            ->line("Cette épreuve est prévue le **{$scheduledStr}** pour une durée de **{$this->rattrapage->duree_minutes} minutes**.")
            ->action('Accéder à la plateforme', url('/login'))
            ->line('Merci de vous connecter à l\'heure prévue pour composer.')
            ->salutation('Cordialement, L’équipe pédagogique E-CRE');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $exam = $this->rattrapage->exam;

        return [
            'type' => 'new_exam_rattrapage',
            'title' => 'Session de rattrapage planifiée',
            'message' => "Une session de rattrapage pour l'examen \"{$exam->titre}\" a été planifiée le " . ($this->rattrapage->scheduled_at ? $this->rattrapage->scheduled_at->format('d/m/Y à H:i') : ''),
            'exam_id' => $exam->id,
            'rattrapage_id' => $this->rattrapage->id,
            'exam_title' => $exam->titre,
            'module_id' => $exam->module_id,
            'module_title' => $exam->module?->titre,
            'scheduled_at' => $this->rattrapage->scheduled_at ? $this->rattrapage->scheduled_at->toISOString() : null,
            'duree_minutes' => $this->rattrapage->duree_minutes,
        ];
    }
}
