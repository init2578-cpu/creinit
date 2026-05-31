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

        $existing = $announcement->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $announcement->likes()->create(['user_id' => $userId]);
        }

        return back()->with('success', 'ok');
    }
}
