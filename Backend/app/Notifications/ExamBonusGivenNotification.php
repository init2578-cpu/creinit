<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamBonusGivenNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ExamResult $result,
        protected User $trainer
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $exam = $this->result->exam;
        $student = $this->result->user;
        $totalScore = $this->result->score + $this->result->bonus;

        return [
            'type'       => 'warning',
            'title'      => 'Bonus attribué sur un examen',
            'message'    => "Le formateur {$this->trainer->name} a attribué un bonus de +{$this->result->bonus} pts à {$student->name} pour l'examen \"{$exam->titre}\" (Note finale: {$totalScore}/20).",
            'exam_id'    => $exam->id,
            'student_id' => $student->id,
            'action_url' => '/admin-scolarite/exams',
        ];
    }
}
