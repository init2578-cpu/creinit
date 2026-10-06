<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'user_id',
        'titre',
        'type',
        'document_path',
        'description',
        'scheduled_at',
        'duree_minutes',
        'total_points',
        'is_active',
        'is_practice',
        'is_approved',
        'are_grades_published',
        'is_exclusive_directeur',
    ];

    protected $appends = ['is_online', 'has_ended', 'can_start', 'end_at'];

    /**
     * Determine if the exam is an online quiz.
     */
    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'online' || $this->type === 'quizz',
        );
    }

    /**
     * Get the absolute end time of the exam session.
     */
    protected function endAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scheduled_at ? $this->scheduled_at->addMinutes($this->duree_minutes) : null,
        );
    }

    /**
     * Check if the exam session has officially ended.
     */
    protected function hasEnded(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->isExpired(),
        );
    }

    /**
     * Determine if the student can start the exam now.
     */
    protected function canStart(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active 
                && $this->is_approved
                && (!$this->scheduled_at || now()->isAfter($this->scheduled_at))
                && !$this->isExpired(),
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'is_practice' => 'boolean',
            'are_grades_published' => 'boolean',
            'is_exclusive_directeur' => 'boolean',
            'duree_minutes' => 'integer',
            'total_points' => 'decimal:2',
            'scheduled_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('ordre');
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'exam_group');
    }

    public function particularStudents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_particular_users')->withTimestamps();
    }

    public function rattrapages(): HasMany
    {
        return $this->hasMany(ExamRattrapage::class)->orderBy('scheduled_at', 'asc');
    }

    /**
     * Get active rattrapage session for a specific student right now.
     */
    public function getActiveRattrapageForUser(User $user): ?ExamRattrapage
    {
        return $this->rattrapages()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->get()
            ->first(fn ($r) => $r->can_start && !$r->isExpired());
    }

    /**
     * Get upcoming or active rattrapage session for a specific student.
     */
    public function getUpcomingOrActiveRattrapageForUser(User $user): ?ExamRattrapage
    {
        return $this->rattrapages()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->get()
            ->first(fn ($r) => !$r->isExpired());
    }

    /**
     * Check if a specific user can start the exam (either regular or rattrapage session).
     */
    public function canStartForUser(User $user): bool
    {
        if ($this->can_start) {
            return true;
        }

        return $this->getActiveRattrapageForUser($user) !== null;
    }

    /**
     * Check if the exam session has officially ended.
     */
    public function isExpired(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        // Buffer of 1 minute to allow for submission network time
        return now()->isAfter($this->scheduled_at->addMinutes($this->duree_minutes + 1));
    }
}
