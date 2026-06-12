<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Asset;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Notifications\AssetAddedNotification;
use App\Notifications\DefectiveAssetReported;
use Illuminate\Support\Facades\Notification;

class AssetController extends Controller
{
    /**
     * Display a listing of all assets.
     */
    public function index(): Response
    {
        $query = Asset::with(['activeLoan.user', 'activeLoan.giver', 'registeredBy'])->orderBy('nom');
        
        if (!auth()->user()->hasRole('Directeur')) {
            $query->where('is_hidden', false)
                  ->where(function ($q) {
                      $q->where('is_approved', true)
                        ->orWhere('registered_by', auth()->id());
                  });
        }

        return Inertia::render('Logistics/AssetsIndex', [
            'assets' => $query->get()->map(function($asset) {
                $activeLoan = $asset->activeLoan->first();
                return [
                    'id' => $asset->id,
                    'uuid' => $asset->uuid,
                    'nom' => $asset->nom,
                    'serie' => $asset->serie,
                    'emplacement' => $asset->emplacement,
                    'etat' => $asset->etat,
                    'status' => $asset->status,
                    'is_hidden' => $asset->is_hidden,
                    'is_approved' => $asset->is_approved,
                    'borrower' => $activeLoan && $activeLoan->user ? [
                        'name' => $activeLoan->user->name,
                        'email' => $activeLoan->user->email,
                        'telephone' => $activeLoan->user->telephone,
                    ] : null,
                    'giver' => $activeLoan && $activeLoan->giver ? [
                        'name' => $activeLoan->giver->name,
                    ] : null,
                    'registered_by' => $asset->registeredBy ? [
                        'name' => $asset->registeredBy->name,
                    ] : null,
                    'created_at' => $asset->created_at->format('d/m/Y'),
                ];
            })
        ]);
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'   => 'required|string|max:255',
            'serie' => 'nullable|string|max:255',
            'emplacement' => 'required|string|max:255',
            'etat'  => 'required|string|in:bon,endommagé,hors_service',
            'status'=> 'required|string|in:disponible,preté,maintenance,en_attente_validation',
            'is_hidden' => 'boolean',
        ]);

        $validated['registered_by'] = $request->user()->id;
        $validated['is_approved'] = $request->user()->hasRole('Directeur');

        $asset = Asset::create($validated);

        // Notify directors (and secretaries) when a non-director adds an asset
        if (!$request->user()->hasRole('Directeur')) {
            $directors = User::role('Directeur')->get();
            Notification::send($directors, new AssetAddedNotification($asset, $request->user()));
        }

        if (in_array($asset->etat, ['endommagé', 'hors_service'])) {
            $staff = User::role(['Directeur', 'Secrétaire'])->get();
            Notification::send($staff, new DefectiveAssetReported($asset));
        }

        return back()->with('success', 'Matériel ajouté avec succès.');
    }

    /**
     * Update the specified asset.
     */
    public function update(Request $request, Asset $asset)
    {
        if (!auth()->user()->hasRole('Directeur')) {
            abort(403);
        }

        $validated = $request->validate([
            'nom'   => 'required|string|max:255',
            'serie' => 'nullable|string|max:255',
            'emplacement' => 'required|string|max:255',
            'etat'  => 'required|string|in:bon,endommagé,hors_service',
            'status'=> 'required|string|in:disponible,preté,maintenance,en_attente_validation',
            'is_hidden' => 'boolean',
        ]);

        $asset->update($validated);

        if (in_array($asset->etat, ['endommagé', 'hors_service'])) {
            $staff = User::role(['Directeur', 'Secrétaire'])->get();
            Notification::send($staff, new DefectiveAssetReported($asset));
        }

        return back()->with('success', 'Matériel mis à jour.');
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(Asset $asset)
    {
        if (!auth()->user()->hasRole('Directeur')) {
            abort(403);
        }

        // Don't allow deletion if currently on loan
        if ($asset->status === 'preté' || $asset->activeLoan()->exists()) {
            return back()->with('error', 'Impossible de supprimer un matériel actuellement prêté.');
        }

        // Remove associated loans to satisfy the foreign key constraint
        $asset->loans()->delete();

        $asset->delete();

        return back()->with('success', 'Matériel supprimé.');
    }

    /**
     * Approve a newly created asset.
     */
    public function approve(Asset $asset)
    {
        if (!auth()->user()->hasRole('Directeur')) {
            abort(403);
        }

        $asset->update(['is_approved' => true]);

        return back()->with('success', 'Le matériel a été approuvé.');
    }
}
