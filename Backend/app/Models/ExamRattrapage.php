<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ExamRattrapage extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'titre',
        'scheduled_at',
        'duree_minutes',
        'instructions',
        'created_by',
    ];

    protected $appends = ['has_ended', 'can_start', 'end_at'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duree_minutes' => 'integer',
        ];
    }

    /**
     * Get the absolute end time of the rattrapage session.
     */
    protected function endAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scheduled_at ? $this->scheduled_at->addMinutes($this->duree_minutes) : null,
        );
    }

    /**
     * Check if the rattrapage session has ended.
     */
    protected function hasEnded(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->isExpired(),
        );
    }

    /**
     * Determine if a student can start the rattrapage now.
     */
    protected function canStart(): Attribute
    {
        return Attribute::make(
            get: fn () => (!$this->scheduled_at || now()->isAfter($this->scheduled_at)) && !$this->isExpired(),
        );
    }

    public function isExpired(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        // Buffer of 1 minute to allow for submission network latency
        return now()->isAfter($this->scheduled_at->addMinutes($this->duree_minutes + 1));
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_rattrapage_user')
            ->withPivot(['attendance_id', 'motif_justification', 'status'])
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'exam_rattrapage_id');
    }
}
