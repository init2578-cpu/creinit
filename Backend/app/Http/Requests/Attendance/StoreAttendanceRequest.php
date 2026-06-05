<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
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
            'group_id' => [
                'required',
                'integer',
                Rule::exists('groups', 'id'),
            ],
            'latitude' => [
                $gpsRequired ? 'required' : 'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                $gpsRequired ? 'required' : 'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required'  => 'La latitude GPS est requise.',
            'longitude.required' => 'La longitude GPS est requise.',
        ];
    }
}
