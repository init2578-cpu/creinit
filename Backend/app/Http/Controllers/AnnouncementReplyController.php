<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementReply;
use App\Notifications\AnnouncementReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AnnouncementReplyController extends Controller
{
    /**
     * Store a newly created reply in storage.
     */
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $reply = $announcement->replies()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        // Notify the announcement author (if they are not the one replying)
        $reply->load(['user', 'announcement']);
        $author = $announcement->user;
        if ($author && $author->id !== $request->user()->id) {
            $author->notify(new AnnouncementReplyNotification($reply));
        }

        return back();
    }

    /**
     * Remove the specified reply from storage.
     */
    public function destroy(AnnouncementReply $reply, Request $request): RedirectResponse
    {
        if ($request->user()->id !== $reply->user_id && !$request->user()->hasRole('Directeur')) {
            abort(403, 'Action non autorisée.');
        }

        $reply->delete();

        return back();
    }
}
