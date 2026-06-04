<?php

declare(strict_types=1);

namespace App\Http\Requests\ChapterProgress;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitChapterProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Anti-IDOR: only the designated trainer of the group can submit chapter progress.
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

        return in_array($group->formateur_id, $allowedFormateurIds, true);
    }

    public function rules(): array
    {
        return [
            'group_id' => [
                'required',
                'integer',
                Rule::exists('groups', 'id'),
            ],
            'chapter_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'chapter_ids.*' => [
                'required',
                'integer',
                Rule::exists('chapters', 'id'),
                // Unique check per chapter/group is harder in a batch rule, 
                // we will handle it in the controller for better error messages
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chapter_id.unique' => 'Ce chapitre a déjà été soumis pour ce groupe.',
        ];
    }
}
