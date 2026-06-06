<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Asset;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Notifications\DefectiveAssetReported;
use Illuminate\Support\Facades\Notification;

class AssetController extends Controller
{
    /**
     * Display a listing of all assets.
     */
    public function index(): Response
    {
        return Inertia::render('Logistics/AssetsIndex', [
            'assets' => Asset::with(['activeLoan.user', 'activeLoan.giver'])->orderBy('nom')->get()->map(function($asset) {
                $activeLoan = $asset->activeLoan->first();
                return [
                    'id' => $asset->id,
                    'uuid' => $asset->uuid,
                    'nom' => $asset->nom,
                    'serie' => $asset->serie,
                    'etat' => $asset->etat,
                    'status' => $asset->status,
                    'borrower' => $activeLoan && $activeLoan->user ? [
                        'name' => $activeLoan->user->name,
                        'email' => $activeLoan->user->email,
                        'telephone' => $activeLoan->user->telephone,
                    ] : null,
                    'giver' => $activeLoan && $activeLoan->giver ? [
                        'name' => $activeLoan->giver->name,
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
            'etat'  => 'required|string|in:bon,endommagé,hors_service',
            'status'=> 'required|string|in:disponible,preté,maintenance',
        ]);

        $asset = Asset::create($validated);

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
        $validated = $request->validate([
            'nom'   => 'required|string|max:255',
            'serie' => 'nullable|string|max:255',
            'etat'  => 'required|string|in:bon,endommagé,hors_service',
            'status'=> 'required|string|in:disponible,preté,maintenance',
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
        // Don't allow deletion if currently on loan
        if ($asset->status === 'preté') {
            return back()->with('error', 'Impossible de supprimer un matériel actuellement prêté.');
        }

        $asset->delete();

        return back()->with('success', 'Matériel supprimé.');
    }
}
