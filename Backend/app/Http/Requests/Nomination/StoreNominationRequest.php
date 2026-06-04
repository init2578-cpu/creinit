<?php

declare(strict_types=1);

namespace App\Http\Requests\Nomination;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNominationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Anti-IDOR: only the designated trainer of the group can nominate.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = $this->user();

        if (!$user->isTrainer()) {
            return false;
        }

        // The trainer must own this group (or their tutor if they are a substitute/assistant)
        $group = Group::find($this->input('group_id'));

        if (!$group) {
            return false;
        }

        $allowedFormateurIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $allowedFormateurIds[] = $user->internshipRecord->tuteur_id;
        }

        if (!in_array($group->formateur_id, $allowedFormateurIds, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_id' => [
                'required',
                'integer',
                Rule::exists('groups', 'id'),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['responsable', 'adjoint']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'Cet apprenant a déjà été nommé pour ce rôle dans ce groupe.',
        ];
    }
}
