<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamPendingValidationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Exam $exam,
        protected User $proposedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'info',
            'title'      => 'Examen en attente de validation',
            'message'    => "L'examen \"{$this->exam->titre}\" a été proposé par {$this->proposedBy->name} et nécessite votre validation.",
            'exam_id'    => $this->exam->id,
            'action_url' => '/admin-scolarite/exams',
        ];
    }
}
