<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_groupe' => ['sometimes', 'string', 'max:255'],
            'module_id' => ['required', 'exists:modules,id'],
            'formateur_id' => ['required', 'exists:users,id'],
            'annee_academique' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'responsable_groupe_id' => ['nullable', 'exists:users,id'],
            'adjoint_groupe_id' => ['nullable', 'exists:users,id', 'different:responsable_groupe_id'],
            'gps_check_required' => ['sometimes', 'boolean'],
        ];
    }
}
