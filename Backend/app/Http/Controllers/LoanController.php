<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreAssetCheckoutRequest;
use App\Models\Asset;
use App\Models\Loan;
use App\Services\SignatureStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function __construct(
        private readonly SignatureStorageService $signatureService,
    ) {}

    /**
     * Display all loans with optional filters.
     */
    public function index(): Response
    {
        $loans = Loan::with(['asset', 'user', 'giver'])
            ->orderByDesc('borrowed_at')
            ->paginate(20);

        $availableAssets = Asset::where('status', 'disponible')
            ->where('is_hidden', false)
            ->where('is_approved', true)
            ->get();
        $users = \App\Models\User::all();

        return Inertia::render('Logistics/CheckoutAsset', [
            'loans'           => $loans,
            'availableAssets' => $availableAssets,
            'users'           => $users,
        ]);
    }

    /**
     * Checkout an asset to a user (with digital signature).
     * Secrétaire can VIEW loans but cannot create one.
     */
    public function checkout(StoreAssetCheckoutRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Secrétaire can only view the flux, not create loans
        if ($user->hasRole('Secrétaire') && !$user->hasRole('Directeur')) {
            abort(403, 'Le Secrétaire ne peut pas effectuer d\'emprunts.');
        }

        DB::transaction(function () use ($request, $user): void {
            // Store the signature
            $signaturePath = $this->signatureService->store(
                $request->validated('signature'),
            );

            $isStaff = $user->hasRole(['Directeur', 'Secrétaire']);
            $loanStatus = $isStaff ? 'approved' : 'pending';

            // Create the loan for the SELECTED student
            Loan::create([
                'asset_id'       => $request->validated('asset_id'),
                'user_id'        => $request->validated('user_id'),
                'giver_id'       => $user->id,
                'borrowed_at'    => now(),
                'signature_path' => $signaturePath,
                'status'         => $loanStatus,
            ]);

            // Update asset status
            $newAssetStatus = $isStaff ? 'preté' : 'en_attente_validation';
            Asset::where('id', $request->validated('asset_id'))
                ->update(['status' => $newAssetStatus]);
        });

        $message = $user->hasRole(['Directeur', 'Secrétaire']) 
            ? 'Équipement emprunté avec succès.'
            : 'Demande d\'emprunt enregistrée. En attente de validation par la Direction.';

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Return a loaned asset.
     */
    public function returnAsset(Loan $loan): RedirectResponse
    {
        if (!$loan->isActive()) {
            return redirect()
                ->back()
                ->with('error', 'Cet emprunt n\'est pas actif ou a déjà été clôturé.');
        }

        DB::transaction(function () use ($loan): void {
            $loan->update(['returned_at' => now()]);
            $loan->asset->update(['status' => 'disponible']);
        });

        return redirect()
            ->back()
            ->with('success', 'Équipement retourné avec succès.');
    }

    /**
     * Approve a pending loan.
     */
    public function approve(Loan $loan): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Directeur', 'Secrétaire'])) {
            abort(403);
        }

        if (!$loan->isPending()) {
            return back()->with('error', 'Ce prêt n\'est pas en attente de validation.');
        }

        DB::transaction(function () use ($loan): void {
            $loan->update(['status' => 'approved']);
            $loan->asset->update(['status' => 'preté']);
        });

        return back()->with('success', 'Prêt approuvé avec succès.');
    }

    /**
     * Reject a pending loan.
     */
    public function reject(Loan $loan): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Directeur', 'Secrétaire'])) {
            abort(403);
        }

        if (!$loan->isPending()) {
            return back()->with('error', 'Ce prêt n\'est pas en attente de validation.');
        }

        DB::transaction(function () use ($loan): void {
            $loan->update(['status' => 'rejected', 'returned_at' => now()]);
            $loan->asset->update(['status' => 'disponible']);
        });

        return back()->with('success', 'Prêt refusé.');
    }

    /**
     * View the signature of a loan.
     */
    public function signature(Loan $loan)
    {
        // Only allow staff members (Directeur, Secrétaire) to view signatures
        if (!auth()->user()->hasRole(['Directeur', 'Secrétaire'])) {
            abort(403);
        }

        if (!$loan->signature_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($loan->signature_path)) {
            abort(404);
        }

        return response()->file(
            \Illuminate\Support\Facades\Storage::disk('local')->path($loan->signature_path),
            ['Content-Type' => 'image/png']
        );
    }
}
