<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'user_id',
        'score',
        'bonus',
        'finished_at',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'bonus' => 'float',
            'finished_at' => 'datetime',
            'answers' => 'json',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
