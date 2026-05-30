<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'general' => Setting::where('group', 'general')->get()->toArray(),
                'attendance' => Setting::where('group', 'attendance')->get()->toArray(),
                'notifications' => Setting::where('group', 'notifications')->get()->toArray(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $settings = $request->input('settings');

        foreach ($settings as $key => $value) {
            // Only update if the setting exists to avoid issues
            Setting::where('key', $key)->update(['value' => $value]);
        }

        return back()->with('success', 'Tous les paramètres ont été mis à jour.');
    }
}
