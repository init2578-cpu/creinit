<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExerciseSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExerciseGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $submission;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExerciseSubmission $submission)
    {
        $this->submission = $submission->load(['chapter.module']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $moduleTitle = $this->submission->chapter->module->titre;
        $chapterTitle = $this->submission->chapter->titre;
        $grade = $this->submission->grade;
        $total = $this->submission->chapter->exercise_points ?? 20;

        $message = (new MailMessage)
                    ->subject("Résultat de votre exercice : $chapterTitle")
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line("Votre exercice pour le chapitre **$chapterTitle** (Module : $moduleTitle) a été corrigé.")
                    ->line("Note obtenue : **$grade / $total**");

        if ($this->submission->trainer_feedback) {
            $message->line("Commentaire du formateur :")
                    ->line("_{$this->submission->trainer_feedback}_");
        }

        return $message->action('Voir le résultat', route('student.dashboard'))
                    ->line('Félicitations pour votre travail !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Exercice corrigé',
            'submission_id' => $this->submission->id,
            'module_title' => $this->submission->chapter->module->titre,
            'chapter_title' => $this->submission->chapter->titre,
            'grade' => $this->submission->grade,
            'message' => "Votre exercice pour le chapitre '{$this->submission->chapter->titre}' a été corrigé.",
            'type' => 'exercise_graded',
        ];
    }
}
