<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\ExerciseSubmission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'is_active',
        'telephone',
        'adresse',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /**
     * Groups where this user is the trainer (formateur).
     */
    public function groupsAsFormateur(): HasMany
    {
        return $this->hasMany(Group::class, 'formateur_id');
    }

    /**
     * Groups where this user is the group supervisor (responsable).
     */
    public function groupsAsResponsable(): HasMany
    {
        return $this->hasMany(Group::class, 'responsable_groupe_id');
    }

    /**
     * Nominations where this user is the proposed group supervisor.
     */
    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    /**
     * Groups where this user is enrolled as a student.
     */
    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user');
    }

    /**
     * Submissions made by this user.
     */
    public function exerciseSubmissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }

    /**
     * Get the application used to create this user account.
     */
    public function application(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Application::class);
    }

    /**
     * Get the attendances tracked for this user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
    /**
     * Get the internship record associated with the user.
     */
    public function internshipRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InternshipRecord::class);
    }

    /**
     * Chapters submitted for validation by this trainer.
     */
    public function submittedChapters(): HasMany
    {
        return $this->hasMany(ChapterGroupProgress::class, 'submitted_by');
    }

    /**
     * Certificates earned by this user.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the announcements posted by the user.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Get the URL of the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? '/storage/' . $this->profile_photo_path
            : null;
    }

    protected $appends = [
        'profile_photo_url',
    ];
}
