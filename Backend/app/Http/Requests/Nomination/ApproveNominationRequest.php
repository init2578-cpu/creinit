<?php

declare(strict_types=1);

namespace App\Http\Requests\Nomination;

use Illuminate\Foundation\Http\FormRequest;

class ApproveNominationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only Secrétaire can approve nominations.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = $this->user();

        return $user->hasRole('Secrétaire') || $user->hasRole('Directeur');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
        ];
    }
}
