<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssetAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Asset $asset,
        protected User $addedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'info',
            'title'      => 'Nouveau matériel ajouté',
            'message'    => "\"{$this->asset->nom}\" a été ajouté à l'inventaire par {$this->addedBy->name}. En attente de votre validation.",
            'asset_id'   => $this->asset->id,
            'action_url' => '/assets',
        ];
    }
}
