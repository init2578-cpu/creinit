<?php

declare(strict_types=1);

namespace App\Http\Requests\Scolarite;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisée car le middleware auth s'en occupe
    }

    public function rules(): array
    {
        $today = now()->startOfDay()->toDateString();

        return [
            'module_id' => [
                'required', 
                Rule::exists('modules', 'id')->where(function ($query) use ($today) {
                    $query->where('is_active', true)
                          ->where(function ($q) use ($today) {
                              $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                          })->where(function ($q) use ($today) {
                              $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                          });
                })
            ],
            'has_cni' => ['nullable', 'boolean'],
            'cni_recto' => [
                Rule::requiredIf(fn() => $this->boolean('has_cni', true) && !$this->hasFile('cni')),
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'
            ],
            'cni_verso' => [
                Rule::requiredIf(fn() => $this->boolean('has_cni', true) && !$this->hasFile('cni')),
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'
            ],
            'other_identity_doc' => [
                Rule::requiredIf(fn() => !$this->boolean('has_cni', true)),
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'
            ],
            'cni' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'diploma' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'commentaires' => ['nullable', 'string'],
            
            // New fields
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:20', 'unique:users,telephone'],
            'adresse_reelle' => ['required', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'niveau_etude' => ['required', 'string', 'max:255'],
            'dernier_diplome_libelle' => ['required', 'string', 'max:255'],
            'fonction' => ['required', 'string', 'max:255'],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'sexe' => ['required', 'string', 'in:M,F'],
        ];
    }
}
