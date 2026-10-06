<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'module_id',
        'type',
        'score',
        'group_id',
        'issued_at',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'user_id'   => 'integer',
            'module_id' => 'integer',
            'group_id'  => 'integer',
            'score'     => 'float',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $cert): void {
            if (empty($cert->uuid)) {
                $cert->uuid = (string) Str::uuid();
            }
            if (empty($cert->type)) {
                $cert->type = 'reussite';
            }
        });
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
