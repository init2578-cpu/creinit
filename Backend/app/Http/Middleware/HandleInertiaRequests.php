<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'profile_photo_url' => $request->user()->profile_photo_url,
                    'roles' => $request->user()->getRoleNames(),
                    'is_trainer' => $request->user()->isTrainer(),
                    'telephone' => $request->user()->telephone,
                    'adresse' => $request->user()->adresse,
                    'has_passkeys' => $request->user()->hasPasskeysEnabled(),
                    'unread_notifications' => $request->user()->unreadNotifications()->latest()->limit(10)->get(),
                    'unread_notifications_count' => $request->user()->unreadNotifications()->count(),
                    'unread_messages_count' => \App\Models\ContactMessage::where('is_read', false)->count(),
                    'pending_nominations_count' => ($request->user()->hasRole('Directeur') || $request->user()->hasRole('Secrétaire')) ? \App\Models\Nomination::where('status', 'pending')->count() : 0,
                    'unread_exams_count' => $request->user()->unreadNotifications()->where('type', \App\Notifications\ExamResultGradedNotification::class)->count(),
                    'unread_exercises_count' => $request->user()->unreadNotifications()->where('type', \App\Notifications\ExerciseGradedNotification::class)->count(),
                    'unread_progressions_count' => $request->user()->unreadNotifications()->where('type', \App\Notifications\ChapterSubmittedNotification::class)->count(),
                    'unread_rejections_count' => $request->user()->unreadNotifications()->where('type', \App\Notifications\ChapterRejectedNotification::class)->count(),
                    'unread_announcements_count' => call_user_func(function () use ($request) {
                        $user = $request->user();
                        $userRoles = $user->getRoleNames()->toArray();
                        $readIds = \App\Models\AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id');
                        
                        $query = \App\Models\Announcement::where(function ($q) {
                            $q->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                        });

                        if (!$user->hasRole('Directeur')) {
                            $query->where(function ($q) use ($userRoles) {
                                $q->whereNull('visibility_roles')
                                  ->orWhereJsonContains('visibility_roles', $userRoles);
                            });
                        }

                        return $query->whereNotIn('id', $readIds)->count();
                    }),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
