<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Nomination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NominationProposedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Nomination $nomination,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group   = $this->nomination->group;
        $nominee = $this->nomination->user;
        $trainer = $this->nomination->nominator;

        return (new MailMessage())
            ->subject('Nouvelle nomination — Responsable de Groupe')
            ->greeting('Bonjour,')
            ->line("Le formateur **{$trainer->name}** propose **{$nominee->name}** comme Responsable du groupe **{$group->nom_groupe}**.")
            ->line('Veuillez valider ou rejeter cette nomination.')
            ->action('Voir la nomination', url("/nominations/{$this->nomination->id}"))
            ->salutation('Cordialement, E-CRE Kolda');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roleLabel = $this->nomination->role === 'responsable' ? 'Chef de groupe' : 'Adjoint';
        
        return [
            'type'           => 'nomination_proposed',
            'title'          => 'Nouvelle proposition',
            'message'        => "{$this->nomination->nominator->name} propose {$this->nomination->user->name} comme {$roleLabel}.",
            'nomination_id'  => $this->nomination->id,
            'role'           => $this->nomination->role,
            'group_name'     => $this->nomination->group->nom_groupe,
        ];
    }
}
