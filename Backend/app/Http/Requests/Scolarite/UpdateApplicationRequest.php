<?php

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $application = $this->route('application');
        return [
            'module_id' => ['required', 'exists:modules,id'],
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($application->user_id)],
            'telephone' => ['required', 'string', 'max:20', \Illuminate\Validation\Rule::unique('users')->ignore($application->user_id)],
            'adresse_reelle' => ['required', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'niveau_etude' => ['required', 'string', 'max:255'],
            'dernier_diplome_libelle' => ['required', 'string', 'max:255'],
            'fonction' => ['required', 'string', 'max:255'],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'commentaires' => ['nullable', 'string'],
            'sexe' => ['required', 'string', 'in:M,F'],
            'has_cni' => ['nullable', 'boolean'],
            'cni' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cni_recto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cni_verso' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'other_identity_doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'diploma' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_cni' => ['nullable', 'boolean'],
            'remove_cni_recto' => ['nullable', 'boolean'],
            'remove_cni_verso' => ['nullable', 'boolean'],
            'remove_other_identity_doc' => ['nullable', 'boolean'],
            'remove_diploma' => ['nullable', 'boolean'],
        ];
    }
}
