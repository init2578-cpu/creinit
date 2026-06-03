<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements visible to the user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userRoles = $user->getRoleNames()->toArray();

        $announcements = Announcement::with(['user:id,name,email,profile_photo_path', 'replies.user:id,name,profile_photo_path', 'likes'])
            ->when(!$user->hasRole('Directeur'), function ($query) use ($userRoles) {
                $query->where(function ($q) use ($userRoles) {
                    $q->whereNull('visibility_roles')
                      ->orWhereJsonContains('visibility_roles', $userRoles);
                });
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15);

        // Annotate each announcement with reaction counts and user's selection; mask anonymous authors
        $announcements->getCollection()->transform(function ($announcement) use ($user) {
            $announcement->heart_count      = $announcement->likes->where('type', 'heart')->count();
            $announcement->thumb_up_count   = $announcement->likes->where('type', 'thumb_up')->count();
            $announcement->thumb_down_count = $announcement->likes->where('type', 'thumb_down')->count();
            
            $userLike = $announcement->likes->where('user_id', $user->id)->first();
            $announcement->user_reaction    = $userLike ? $userLike->type : null;
            
            unset($announcement->likes); // don't leak full likes list to frontend

            if (!$user->hasRole('Directeur') && $announcement->is_anonymous) {
                $announcement->user = null;
            }
            return $announcement;
        });

        // Record these announcements as read for this user
        $announcementIds = $announcements->pluck('id')->toArray();
        if (!empty($announcementIds)) {
            $records = array_map(function ($id) use ($user) {
                return [
                    'user_id' => $user->id,
                    'announcement_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $announcementIds);

            \App\Models\AnnouncementRead::upsert(
                $records, 
                ['user_id', 'announcement_id'], 
                ['updated_at']
            );
        }

        return Inertia::render('Community/Index', [
            'announcements' => $announcements,
            'availableRoles' => Role::all(['id', 'name']),
            'canPost' => $user->hasAnyRole(['Directeur', 'Secrétaire', 'Formateur']),
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizePost();

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'category'         => 'required|string|in:info,warning,event,success',
            'visibility_roles' => 'nullable|array',
            'is_pinned'        => 'boolean',
            'is_anonymous'     => 'boolean',
            'expires_at'       => 'nullable|date',
            'files'            => 'nullable|array',
            'files.*'          => 'file|max:20480', // 20MB limit
        ]);

        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('announcements', 'public');
                $attachments[] = [
                    'path'      => $path,
                    'name'      => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size'      => $file->getSize(),
                ];
            }
        }

        $announcementData = array_merge($validated, ['attachments' => $attachments]);
        unset($announcementData['files']);

        $request->user()->announcements()->create($announcementData);

        return redirect()->route('community.index')
            ->with('success', 'Votre message a été publié dans l\'espace communauté.');
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        if ($request->user()->id !== $announcement->user_id && !$request->user()->hasRole('Directeur')) {
            abort(403, 'Action non autorisée.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|in:info,warning,event,success',
            'expires_at' => 'nullable|date|after:now',
            'is_pinned' => 'boolean',
            'is_anonymous' => 'boolean',
            'visibility_roles' => 'nullable|array',
        ]);

        $announcement->update($validated);

        return redirect()->route('community.index');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement, Request $request): RedirectResponse
    {
        if ($request->user()->id !== $announcement->user_id && !$request->user()->hasRole('Directeur')) {
            abort(403);
        }

        // Delete associated files
        // When using soft deletes, we keep the attachments in case the message is restored.
        // If we want to purge them, we should do it in a forceDelete operation.
        /*
        if ($announcement->attachments) {
            foreach ($announcement->attachments as $attachment) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment['path']);
            }
        }
        */

        $announcement->delete();

        return redirect()->route('community.index');
    }

    /**
     * Check if the user is authorized to post.
     */
    private function authorizePost(): void
    {
        if (!auth()->user()->hasAnyRole(['Directeur', 'Secrétaire', 'Formateur'])) {
            abort(403, 'Vous n\'êtes pas autorisé à publier des messages.');
        }
    }
}
