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
        'chapter_id',
        'titre',
        'description',
        'content',
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
            'chapter_id' => 'integer',
            'ordre' => 'integer',
            'quota_heures' => 'integer',
        ];
    }

    /**
     * The chapter this phase belongs to.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
