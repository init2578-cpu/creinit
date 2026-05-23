<?php

declare(strict_types=1);

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisée car le middleware auth s'en occupe
    }

    public function rules(): array
    {
        return [
            'module_id' => ['required', 'exists:modules,id'],
            'cni' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'], // 2MB as per current server limit
            'diploma' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'commentaires' => ['nullable', 'string'],
            
            // New fields
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse_reelle' => ['required', 'string', 'max:255'],
            'date_naissance' => ['required', 'date', 'before_or_equal:now -7 years'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'niveau_etude' => ['required', 'string', 'max:255'],
            'dernier_diplome_libelle' => ['required', 'string', 'max:255'],
            'fonction' => ['required', 'string', 'max:255'],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'sexe' => ['required', 'string', 'in:M,F'],
        ];
    }
}
