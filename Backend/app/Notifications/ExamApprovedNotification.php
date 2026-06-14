<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Exam $exam
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'success',
            'title'      => 'Proposition d\'examen validée',
            'message'    => "Votre proposition d'examen \"{$this->exam->titre}\" a été validée par la Direction.",
            'exam_id'    => $this->exam->id,
            'action_url' => '/admin-scolarite/exams',
        ];
    }
}
