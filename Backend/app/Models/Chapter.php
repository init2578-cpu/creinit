<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    /** @use HasFactory<\Database\Factories\ChapterFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'titre',
        'ordre',
        'content',
        'video_url',
        'video_position',
        'attachments',
        'is_published',
        'is_approved',
        'exercise_type',
        'exercise_title',
        'exercise_instructions',
        'exercise_points',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordre'        => 'integer',
            'module_id'    => 'integer',
            'attachments'  => 'json',
            'is_published' => 'boolean',
            'is_approved'  => 'boolean',
            'exercise_points' => 'float',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /**
     * The module this chapter belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Submissions for this chapter.
     */
    public function exerciseSubmissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }

    /**
     * Questions associated with this chapter (for exercises).
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('ordre');
    }
}
