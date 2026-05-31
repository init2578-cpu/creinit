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

        $announcements = Announcement::with('user:id,name,email,profile_photo_path')
            ->where(function ($query) use ($userRoles) {
                $query->whereNull('visibility_roles')
                      ->orWhereJsonContains('visibility_roles', $userRoles);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15);

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
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement, Request $request): RedirectResponse
    {
        if ($request->user()->id !== $announcement->user_id && !$request->user()->hasRole(['Directeur', 'Secrétaire'])) {
            abort(403);
        }

        // Delete associated files
        if ($announcement->attachments) {
            foreach ($announcement->attachments as $attachment) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment['path']);
            }
        }

        $announcement->delete();

        return redirect()->route('community.index')
            ->with('success', 'Le message a été supprimé.');
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
