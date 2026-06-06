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

        $availableAssets = Asset::where('status', 'disponible')->get();
        $users = \App\Models\User::all();

        return Inertia::render('Logistics/CheckoutAsset', [
            'loans'           => $loans,
            'availableAssets' => $availableAssets,
            'users'           => $users,
        ]);
    }

    /**
     * Checkout an asset to a user (with digital signature).
     */
    public function checkout(StoreAssetCheckoutRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            // Store the signature
            $signaturePath = $this->signatureService->store(
                $request->validated('signature'),
            );

            // Create the loan for the SELECTED student
            Loan::create([
                'asset_id'       => $request->validated('asset_id'),
                'user_id'        => $request->validated('user_id'),
                'giver_id'       => $user->id,
                'borrowed_at'    => now(),
                'signature_path' => $signaturePath,
            ]);


            // Update asset status
            Asset::where('id', $request->validated('asset_id'))
                ->update(['status' => 'prete']);
        });

        return redirect()
            ->back()
            ->with('success', 'Équipement emprunté avec succès.');
    }

    /**
     * Return a loaned asset.
     */
    public function returnAsset(Loan $loan): RedirectResponse
    {
        if (!$loan->isActive()) {
            return redirect()
                ->back()
                ->with('error', 'Cet emprunt a déjà été clôturé.');
        }

        DB::transaction(function () use ($loan): void {
            $loan->update(['returned_at' => now()]);
            $loan->asset->update(['status' => 'disponible']);
        });

        return redirect()
            ->back()
            ->with('success', 'Équipement retourné avec succès.');
    }
}
