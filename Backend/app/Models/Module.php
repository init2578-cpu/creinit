<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    /** @use HasFactory<\Database\Factories\ModuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code_module',
        'titre',
        'description',
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
            'quota_heures' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /**
     * Scope a query to only include modules that are currently open for enrollment.
     */
    public function scopeActiveForEnrollment($query)
    {
        $today = now()->startOfDay();

        return $query->where(function ($q) use ($today) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
        });
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /**
     * Chapters belonging to this module.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('ordre');
    }

    /**
     * Groups (cohortes) assigned to this module.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Virtual attribute for backward compatibility
     */
    public function getNomModuleAttribute(): string
    {
        return $this->titre;
    }

    /**
     * Certificates issued for this module.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
