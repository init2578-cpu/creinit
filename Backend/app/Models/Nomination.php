<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class Nomination extends Model
{
    use HasFactory, Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'nominated_by',
        'status',
        'validated_by',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_id'     => 'integer',
            'user_id'      => 'integer',
            'nominated_by' => 'integer',
            'validated_by' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /**
     * The group concerned by this nomination.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * The learner nominated as group supervisor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The trainer who proposed the nomination.
     */
    public function nominator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    /**
     * The secretary who validated the nomination.
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
