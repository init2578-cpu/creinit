<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChapterGroupProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChapterRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChapterGroupProgress $progress,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group   = $this->progress->group;
        $chapter = $this->progress->chapter;

        return (new MailMessage())
            ->subject('Chapitre rejeté')
            ->greeting('Bonjour,')
            ->line("La validation de votre chapitre **\"{$chapter->titre}\"** pour le groupe **{$group->nom_groupe}** a été refusée.")
            ->line('Veuillez vérifier vos émargements et resoumettre si nécessaire.')
            ->action('Voir la progression', url("/groups/{$group->id}/chapter-progress"))
            ->salutation('Cordialement, E-CRE Kolda');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'chapter_rejected',
            'title'         => 'Progression rejetée',
            'message'       => "La validation du chapitre \"{$this->progress->chapter->titre}\" a été refusée par le groupe.",
            'action_url'    => url("/chapter-progress/groups"),
            'progress_id'   => $this->progress->id,
            'group_id'      => $this->progress->group_id,
            'chapter_id'    => $this->progress->chapter_id,
            'chapter_title' => $this->progress->chapter->titre,
            'group_name'    => $this->progress->group->nom_groupe,
        ];
    }
}
