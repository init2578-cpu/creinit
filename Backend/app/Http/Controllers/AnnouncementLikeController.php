<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AnnouncementLikeController extends Controller
{
    /**
     * Toggle like/unlike for an announcement.
     */
    public function toggle(Request $request, Announcement $announcement): RedirectResponse
    {
        $userId = $request->user()->id;
        $type = $request->input('type', 'heart');

        // Allow heart, thumb_up, thumb_down
        if (!in_array($type, ['heart', 'thumb_up', 'thumb_down'])) {
            $type = 'heart';
        }

        $existing = $announcement->likes()->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->type === $type) {
                // Clicking the same reaction toggles it off
                $existing->delete();
            } else {
                // Clicking a different reaction switches to it
                $existing->update(['type' => $type]);
            }
        } else {
            // First time reacting
            $announcement->likes()->create([
                'user_id' => $userId,
                'type' => $type
            ]);
        }

        return back();
    }
}
