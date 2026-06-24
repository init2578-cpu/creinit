<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChapterProposedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Chapter $chapter,
        public readonly User $trainer,
    ) {}

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
        $module = $this->chapter->module;
        return (new MailMessage())
            ->subject('Nouveau contenu de cours proposé')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le formateur **{$this->trainer->name}** a proposé le chapitre **\"{$this->chapter->titre}\"** dans la formation **\"{$module->titre}\"**.")
            ->line('Ce contenu est actuellement en attente de votre validation pour être visible par les apprenants.')
            ->action('Gérer les formations', url("/modules"))
            ->salutation('Cordialement, E-CRE Kolda');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'chapter_proposed',
            'title'         => 'Nouveau contenu proposé',
            'message'       => "Le formateur {$this->trainer->name} a proposé le chapitre \"{$this->chapter->titre}\" dans la formation \"{$this->chapter->module->titre}\".",
            'action_url'    => url("/modules"),
            'chapter_id'    => $this->chapter->id,
            'chapter_title' => $this->chapter->titre,
            'module_id'     => $this->chapter->module_id,
            'module_title'  => $this->chapter->module->titre,
            'trainer_name'  => $this->trainer->name,
        ];
    }
}
