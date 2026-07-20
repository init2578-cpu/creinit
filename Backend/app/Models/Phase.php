<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'titre',
        'description',
        'ordre',
        'quota_heures',
        'start_date',
        'end_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'module_id' => 'integer',
            'ordre' => 'integer',
            'quota_heures' => 'integer',
        ];
    }

    /**
     * The module this phase belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * The chapters in this phase.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('ordre');
    }
}
