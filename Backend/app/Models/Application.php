<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_complet',
        'user_id',
        'module_id',
        'status',
        'cni_path',
        'cni_recto_path',
        'cni_verso_path',
        'has_cni',
        'other_identity_doc_path',
        'diploma_path',
        'commentaires',
        'adresse_reelle',
        'date_naissance',
        'lieu_naissance',
        'niveau_etude',
        'dernier_diplome_libelle',
        'fonction',
        'etablissement',
        'telephone',
        'sexe',
    ];

    protected $casts = [
        'has_cni' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
