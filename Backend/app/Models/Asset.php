<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'nom',
        'serie',
        'etat',
        'status',
        'registered_by',
    ];

    // -----------------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Asset $asset): void {
            if (empty($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /**
     * Check if the asset is currently available for loan.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'disponible';
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * The active (unreturned) loan if any.
     */
    public function activeLoan(): HasMany
    {
        return $this->hasMany(Loan::class)->whereNull('returned_at');
    }

    /**
     * The user who registered the asset.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
