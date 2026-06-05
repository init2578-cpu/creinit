<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groupId = $this->input('group_id');
        $gpsRequired = true;
        if ($groupId) {
            $group = \App\Models\Group::find($groupId);
            if ($group && !$group->gps_check_required) {
                $gpsRequired = false;
            }
        }

        return [
            'group_id' => ['required', 'exists:groups,id'],
            'date' => ['required', 'date'],
            'latitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric'],
            'longitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric'],
            'attendances' => ['required', 'array'],
            'attendances.*.user_id' => ['required', 'exists:users,id'],
            'attendances.*.status' => ['required', 'in:present,absent_non_justifie,justifie'],
        ];
    }
}
