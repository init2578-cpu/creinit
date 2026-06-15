<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamGradesPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Exam $exam,
        protected User $trainer
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'info',
            'title'      => 'Notes publiées',
            'message'    => "Le formateur {$this->trainer->name} a publié les notes pour l'examen \"{$this->exam->titre}\" (Module: {$this->exam->module->titre}).",
            'exam_id'    => $this->exam->id,
            'action_url' => '/admin-scolarite/exams',
        ];
    }
}
