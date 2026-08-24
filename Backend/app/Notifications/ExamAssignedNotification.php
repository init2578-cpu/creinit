<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamAssignedNotification extends Notification
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
            'type'       => 'info',
            'title'      => 'Nouvel examen attribué',
            'message'    => "L'examen \"{$this->exam->titre}\" vous a été attribué par la Direction.",
            'exam_id'    => $this->exam->id,
            'action_url' => '/admin-scolarite/exams',
        ];
    }
}
