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
        $all = Setting::all();
        $grouped = [
            'general' => Setting::where('group', 'general')->get()->values()->toArray(),
            'attendance' => Setting::where('group', 'attendance')->get()->values()->toArray(),
            'notifications' => Setting::where('group', 'notifications')->get()->values()->toArray(),
        ];

        \Illuminate\Support\Facades\Log::info('Settings Page Loaded', [
            'total_count' => $all->count(),
            'groups_found' => $all->pluck('group')->unique()->toArray(),
            'general_count' => count($grouped['general']),
        ]);

        return Inertia::render('Admin/Settings', [
            'settings' => $grouped,
            'debug_info' => [
                'count' => $all->count(),
                'groups' => $all->pluck('group')->unique()->values()->toArray()
            ]
        ]);
    }

    public function initialize(): RedirectResponse
    {
        $seeder = new \Database\Seeders\SettingSeeder();
        $seeder->run();

        return back()->with('success', 'Les paramètres par défaut ont été initialisés avec succès.');
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
