<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type',
        'description',
        'date_signature',
        'document_path',
        'logo_path',
        'website',
        'status',
        'localisation_gps',
        'heure_signature',
    ];

    protected function casts(): array
    {
        return [
            'date_signature' => 'date',
        ];
    }
}
