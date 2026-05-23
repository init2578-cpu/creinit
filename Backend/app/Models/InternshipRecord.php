<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internship_type',
        'criteria',
        'start_date',
        'end_date',
        'status',
        'motivation_letter_path',
        'cni_path',
        'cv_path',
        'diploma_path',
        'niveau_etude',
        'module_id',
        'formation',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user that owns the internship record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module associated with the internship.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
