<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Ecosystem\StoreEventRequest;
use App\Http\Requests\Ecosystem\StorePartnershipRequest;
use App\Models\Event;
use App\Models\MediaMention;
use App\Models\Partnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EcosystemController extends Controller
{
    /**
     * Display a listing of all ecosystem components.
     */
    public function index(): Response
    {
        return Inertia::render('Ecosystem/Index', [
            'partnerships' => Partnership::orderByDesc('date_signature')->take(5)->get(),
            'events' => Event::orderByDesc('date')->take(5)->get(),
            'mediaMentions' => MediaMention::orderByDesc('created_at')->get(),
        ]);
    }

    public function partnerships(): Response
    {
        return Inertia::render('Ecosystem/PartnershipsIndex', [
            'partnerships' => Partnership::orderByDesc('date_signature')->get(),
        ]);
    }

    public function events(): Response
    {
        return Inertia::render('Ecosystem/EventsIndex', [
            'events' => Event::orderByDesc('date')->get(),
        ]);
    }

    public function publicPartenaires(): Response
    {
        return Inertia::render('Public/Partenaires', [
            'partnerships' => Partnership::where('status', 'actif')
                ->orderByDesc('date_signature')
                ->get(),
        ]);
    }

    /**
     * Store a new partnership.
     */
    public function storePartnership(StorePartnershipRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['localisation_gps'] = $request->localisation_gps;

        if ($request->hasFile('document')) {
            Log::info('Document upload detected in storePartnership', ['name' => $request->file('document')->getClientOriginalName()]);
            $path = $request->file('document')->store('partnerships', 'public');
            Log::info('Document stored', ['path' => $path]);
            $validated['document_path'] = $path;
        } else {
            Log::info('No document file in storePartnership request');
        }

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('partnerships/logos', 'public');
        }

        if ($request->has('website')) {
            $validated['website'] = $request->website;
        }

        Partnership::create($validated);

        return back()->with('success', 'Partenariat ajouté avec succès.');
    }

    public function updatePartnership(Request $request, Partnership $partnership): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'date_signature' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'localisation_gps' => ['nullable', 'string', 'max:255'],
            'heure_signature' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('document')) {
            Log::info('Document upload detected in updatePartnership', ['name' => $request->file('document')->getClientOriginalName()]);
            if ($partnership->document_path) {
                Storage::disk('public')->delete($partnership->document_path);
            }
            $path = $request->file('document')->store('partnerships', 'public');
            Log::info('Document updated', ['path' => $path]);
            $validated['document_path'] = $path;
        } else {
            Log::info('No document file in updatePartnership request');
        }

        if ($request->hasFile('logo')) {
            if ($partnership->logo_path) {
                Storage::disk('public')->delete($partnership->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('partnerships/logos', 'public');
        }

        if ($request->has('website')) {
            $validated['website'] = $request->website;
        }

        $partnership->update($validated);

        return back()->with('success', 'Partenariat mis à jour.');
    }

    public function togglePartnershipStatus(Partnership $partnership): RedirectResponse
    {
        $partnership->update([
            'status' => $partnership->status === 'actif' ? 'suspendu' : 'actif'
        ]);
        return back()->with('success', 'Statut du partenariat mis à jour.');
    }

    /**
     * Store a new event.
     */
    public function storeEvent(StoreEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['lieu'] = $request->lieu;

        if ($request->hasFile('image')) {
            Log::info('Image upload detected in storeEvent', ['name' => $request->file('image')->getClientOriginalName()]);
            $path = $request->file('image')->store('events', 'public');
            Log::info('Image stored', ['path' => $path]);
            $validated['image_path'] = $path;
        } else {
            Log::info('No image file in storeEvent request');
        }

        Event::create($validated);

        return back()->with('success', 'Événement ajouté avec succès.');
    }

    public function updateEvent(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'type_activite' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'audience_estimee' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'heure_debut' => ['nullable', 'string'],
            'heure_fin' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('image')) {
            Log::info('Image upload detected in updateEvent', ['name' => $request->file('image')->getClientOriginalName()]);
            if ($event->image_path) {
                Storage::disk('public')->delete($event->image_path);
            }
            $path = $request->file('image')->store('events', 'public');
            Log::info('Image updated', ['path' => $path]);
            $validated['image_path'] = $path;
        } else {
            Log::info('No image file in updateEvent request');
        }

        $event->update($validated);

        return back()->with('success', 'Événement mis à jour.');
    }

    public function destroyEvent(Event $event): RedirectResponse
    {
        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }
        $event->delete();
        return back()->with('success', 'Événement supprimé.');
    }

    public function toggleEventStatus(Event $event): RedirectResponse
    {
        $event->update([
            'status' => $event->status === 'actif' ? 'suspendu' : 'actif'
        ]);
        return back()->with('success', 'Statut de l\'événement mis à jour.');
    }

    /**
     * Store a new media mention.
     */
    public function storeMediaMention(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'lien_externe' => ['nullable', 'url'],
            'fichier' => ['nullable', 'file', 'max:10240'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('media', 'public');
            $validated['fichier_path'] = $path;
        }

        MediaMention::create($validated);

        return back()->with('success', 'Mention média ajoutée avec succès.');
    }

    /**
     * Delete a partnership and its associated file.
     */
    public function destroyPartnership(Partnership $partnership): RedirectResponse
    {
        if ($partnership->document_path) {
            Storage::disk('public')->delete($partnership->document_path);
        }

        $partnership->delete();

        return back()->with('success', 'Partenariat supprimé.');
    }
}
