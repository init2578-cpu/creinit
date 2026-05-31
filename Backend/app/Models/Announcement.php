<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\AnnouncementLike;

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
        'attachments',
        'is_anonymous',
        'is_pinned',
        'expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'visibility_roles' => 'array',
        'attachments'      => 'array',
        'is_anonymous'     => 'boolean',
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

    /**
     * Get the replies associated with the announcement.
     */
    public function replies()
    {
        return $this->hasMany(AnnouncementReply::class);
    }

    /**
     * Get the likes for this announcement.
     */
    public function likes()
    {
        return $this->hasMany(AnnouncementLike::class);
    }

    /**
     * Check if a given user has liked this announcement.
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likes->contains('user_id', $userId);
    }
}
