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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id'     => 'integer',
            'group_id'    => 'integer',
            'schedule_id' => 'integer',
            'date'        => 'date',
            'latitude'    => 'float',
            'longitude'   => 'float',
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
}
