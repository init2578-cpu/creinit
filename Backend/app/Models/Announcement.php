<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'visibility_roles',
        'is_pinned',
        'expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'visibility_roles' => 'array',
        'is_pinned'        => 'boolean',
        'expires_at'       => 'datetime',
    ];

    /**
     * Get the user who posted the announcement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
