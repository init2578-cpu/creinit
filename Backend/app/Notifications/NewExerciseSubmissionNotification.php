<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExerciseSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewExerciseSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $submission;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExerciseSubmission $submission)
    {
        $this->submission = $submission->load(['user', 'chapter.module']);
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
        $studentName = $this->submission->user->name;
        $moduleTitle = $this->submission->chapter->module->titre;
        $chapterTitle = $this->submission->chapter->titre;

        return (new MailMessage)
                    ->subject("Nouvel exercice soumis : $moduleTitle")
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line("L'apprenant **$studentName** vient de soumettre son exercice pour le chapitre : **$chapterTitle**.")
                    ->line("Module : **$moduleTitle**")
                    ->action('Consulter la soumission', route('exercises.index'))
                    ->line('Merci de corriger cet exercice dès que possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nouvel exercice soumis',
            'submission_id' => $this->submission->id,
            'student_name' => $this->submission->user->name,
            'module_title' => $this->submission->chapter->module->titre,
            'chapter_title' => $this->submission->chapter->titre,
            'message' => "Nouvel exercice soumis par {$this->submission->user->name}",
            'type' => 'exercise_submission',
        ];
    }
}
