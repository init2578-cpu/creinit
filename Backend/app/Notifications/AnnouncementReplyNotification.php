<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AnnouncementReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AnnouncementReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $announcement = $this->reply->announcement;
        $replier = $this->reply->user;

        return [
            'type'              => 'announcement_reply',
            'title'             => 'Nouvelle réponse à votre message',
            'message'           => ($replier ? $replier->name : 'Quelqu\'un') . ' a répondu à votre message "' . \Illuminate\Support\Str::limit($announcement->title, 40) . '".',
            'announcement_id'   => $announcement->id,
            'announcement_title'=> $announcement->title,
            'replier_name'      => $replier?->name ?? 'Utilisateur Anonyme',
            'reply_preview'     => \Illuminate\Support\Str::limit($this->reply->content, 80),
        ];
    }
}
