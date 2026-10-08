<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class Attendance extends Model
{
    use HasFactory, Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'group_id',
        'schedule_id',
        'date',
        'status',
        'latitude',
        'longitude',
        'is_advance_reported',
        'motif',
        'reported_by',
        'reported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id'             => 'integer',
            'group_id'            => 'integer',
            'schedule_id'         => 'integer',
            'date'                => 'date',
            'latitude'            => 'float',
            'longitude'           => 'float',
            'is_advance_reported' => 'boolean',
            'reported_by'         => 'integer',
            'reported_at'         => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
