<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewExerciseAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Chapter $chapter,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Nouvel exercice disponible')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line("Un nouvel exercice intitulé **\"{$this->chapter->exercise_title}\"** est désormais disponible pour le chapitre **\"{$this->chapter->titre}\"** du module **{$this->chapter->module->titre}**.")
            ->action('Voir l’exercice', url('/login'))
            ->line('Bonne réalisation !')
            ->salutation('Cordialement, L’équipe E-CRE Kolda');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_exercise_available',
            'title' => 'Nouvel Exercice Disponible',
            'message' => "Un nouvel exercice a été publié pour le chapitre \"{$this->chapter->titre}\".",
            'chapter_id' => $this->chapter->id,
            'chapter_title' => $this->chapter->titre,
            'exercise_title' => $this->chapter->exercise_title,
            'module_id' => $this->chapter->module_id,
            'module_title' => $this->chapter->module->titre,
        ];
    }
}
