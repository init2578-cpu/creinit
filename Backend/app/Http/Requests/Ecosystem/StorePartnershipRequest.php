<?php

declare(strict_types=1);

namespace App\Http\Requests\Ecosystem;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:institutionnel,privé,ONG,académique'],
            'description' => ['nullable', 'string'],
            'date_signature' => ['required', 'date'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'website' => ['nullable', 'url', 'max:255'],
            'localisation_gps' => ['nullable', 'string', 'max:255'],
            'heure_signature' => ['nullable', 'string'],
        ];
    }
}
