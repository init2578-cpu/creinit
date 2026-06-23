<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Leave extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'type',
        'date_debut',
        'date_fin',
        'motif',
        'status',
        'document_path',
        'admin_commentaire',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
