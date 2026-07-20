<?php

declare(strict_types=1);

namespace App\Http\Requests\Scolarite;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'formateur_id' => ['required', 'exists:users,id'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before:20:00'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', 'before_or_equal:20:00'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            $dayOfWeek = (int)$this->input('day_of_week');
            $roomId = $this->input('room_id');
            $formateurId = $this->input('formateur_id');

            \Illuminate\Support\Facades\Log::info('Schedule validation debug:', [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dayOfWeek' => $dayOfWeek,
                'roomId' => $roomId,
                'formateurId' => $formateurId,
                'hasRoomConflict' => $this->hasConflict('room_id', $roomId, $dayOfWeek, $startTime, $endTime),
                'hasFormateurConflict' => $this->hasConflict('formateur_id', $formateurId, $dayOfWeek, $startTime, $endTime)
            ]);

            if ($this->hasConflict('room_id', $roomId, $dayOfWeek, $startTime, $endTime)) {
                $validator->errors()->add('room_id', 'Cette salle est déjà occupée sur ce créneau.');
            }

            if ($this->hasConflict('formateur_id', $formateurId, $dayOfWeek, $startTime, $endTime)) {
                $validator->errors()->add('formateur_id', 'Ce formateur est déjà réservé sur ce créneau.');
            }
        });
    }

    private function hasConflict(string $column, $value, int $day, string $start, string $end): bool
    {
        return Schedule::where($column, $value)
            ->where('day_of_week', $day)
            ->whereRaw("start_time::time < ?::time", [$end])
            ->whereRaw("end_time::time > ?::time", [$start])
            ->whereHas('group', function ($query) {
                $query->where('status', 'active');
            })
            ->when($this->route('schedule'), function ($query) {
                $schedule = $this->route('schedule');
                $id = is_object($schedule) ? $schedule->id : $schedule;
                $query->where('id', '!=', $id);
            })
            ->exists();
    }
}
