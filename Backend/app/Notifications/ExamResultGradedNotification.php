<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamResultGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $examResult;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExamResult $examResult)
    {
        $this->examResult = $examResult->load(['exam.module']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email) && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $examTitle = $this->examResult->exam->titre;
        $moduleTitle = $this->examResult->exam->module->titre;
        $score = $this->examResult->score;
        $total = $this->examResult->exam->total_points;

        return (new MailMessage)
                    ->subject("Résultat de votre examen : $examTitle")
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line("Votre note pour l'examen **$examTitle** (Module : $moduleTitle) a été enregistrée.")
                    ->line("Note obtenue : **$score / $total**")
                    ->action('Voir mes notes', route('student.dashboard'))
                    ->line('Merci de votre assiduité.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'exam_id' => $this->examResult->exam_id,
            'exam_title' => $this->examResult->exam->titre,
            'module_title' => $this->examResult->exam->module->titre,
            'score' => $this->examResult->score,
            'message' => "Votre note pour l'examen '{$this->examResult->exam->titre}' est disponible.",
            'type' => 'exam_graded',
        ];
    }
}
