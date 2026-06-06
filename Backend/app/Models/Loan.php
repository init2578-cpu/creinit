<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asset_id',
        'user_id',
        'giver_id',
        'borrowed_at',
        'returned_at',
        'signature_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'asset_id'    => 'integer',
            'user_id'     => 'integer',
            'giver_id'    => 'integer',
            'borrowed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'giver_id');
    }
}
