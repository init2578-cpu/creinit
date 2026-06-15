<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use App\Notifications\LeaveStatusUpdatedNotification;
use App\Notifications\NewLeaveRequestNotification;
use App\Models\User;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        
        $stats = [];
        $currentYear = Carbon::now()->year;
        $today = Carbon::today()->format('Y-m-d');

        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $leaves = Leave::with('user')->orderBy('created_at', 'desc')->get();
            
            $stats = [
                'pending_count' => Leave::where('status', 'en_attente')->count(),
                'active_count' => Leave::where('status', 'approuve')
                                    ->where('date_debut', '<=', $today)
                                    ->where('date_fin', '>=', $today)
                                    ->count(),
                'approved_year_count' => Leave::where('status', 'approuve')
                                            ->whereYear('date_debut', $currentYear)
                                            ->count(),
            ];
        } else {
            $leaves = Leave::with('user')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
            
            $previousLeaves = Leave::where('user_id', $user->id)
                ->whereYear('date_debut', $currentYear)
                ->whereNotIn('type', ['Maternité', 'Maladie'])
                ->whereIn('status', ['approuve', 'en_attente'])
                ->get();

            $accumulatedDays = 0;
            foreach ($previousLeaves as $l) {
                $accumulatedDays += Carbon::parse($l->date_debut)->diffInDays(Carbon::parse($l->date_fin)) + 1;
            }

            $stats = [
                'pending_count' => Leave::where('user_id', $user->id)->where('status', 'en_attente')->count(),
                'consumed_days' => $accumulatedDays,
                'remaining_days' => max(0, 30 - $accumulatedDays),
            ];
        }

        return Inertia::render('Leaves/Index', [
            'leaves' => $leaves,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($overlapError = $this->checkOverlap($validated)) {
            return back()->withErrors(['date_debut' => $overlapError])->withInput();
        }

        if ($error = $this->checkLeaveLimit($validated)) {
            return back()->withErrors(['date_fin' => $error])->withInput();
        }

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leaves_documents', 'private');
        }

        $leave = Leave::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'motif' => $validated['motif'],
            'status' => 'en_attente',
            'document_path' => $documentPath,
        ]);

        $directors = User::role('Directeur')->get();
        foreach ($directors as $director) {
            $director->notify(new NewLeaveRequestNotification($leave));
        }

        return back()->with('success', 'Votre demande de congé a été soumise avec succès.');
    }

    public function update(Request $request, Leave $leaf): RedirectResponse
    {
        if (Auth::id() !== $leaf->user_id) {
            abort(403, 'Action non autorisée.');
        }

        if ($leaf->status !== 'en_attente') {
            return back()->with('error', 'Vous ne pouvez pas modifier une demande déjà traitée.');
        }

        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($overlapError = $this->checkOverlap($validated, $leaf->id)) {
            return back()->withErrors(['date_debut' => $overlapError])->withInput();
        }

        if ($error = $this->checkLeaveLimit($validated, $leaf->id)) {
            return back()->withErrors(['date_fin' => $error])->withInput();
        }

        if ($request->hasFile('document')) {
            if ($leaf->document_path) {
                Storage::disk('private')->delete($leaf->document_path);
            }
            $leaf->document_path = $request->file('document')->store('leaves_documents', 'private');
        }

        $leaf->update([
            'type' => $validated['type'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'motif' => $validated['motif'],
        ]);

        return back()->with('success', 'Votre demande de congé a été mise à jour avec succès.');
    }

    public function updateStatus(Request $request, Leave $leaf): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approuve,rejete',
            'admin_commentaire' => 'nullable|string',
        ]);

        $leaf->update([
            'status' => $validated['status'],
            'admin_commentaire' => $validated['admin_commentaire'],
        ]);

        $leaf->user->notify(new LeaveStatusUpdatedNotification($leaf));

        $message = $validated['status'] === 'approuve' ? 'La demande de congé a été approuvée.' : 'La demande de congé a été rejetée.';

        return back()->with('success', $message);
    }

    public function destroy(Leave $leaf): RedirectResponse
    {
        if (Auth::id() !== $leaf->user_id) {
            abort(403, 'Action non autorisée.');
        }

        if ($leaf->status !== 'en_attente') {
            return back()->with('error', 'Vous ne pouvez pas supprimer une demande déjà traitée.');
        }

        if ($leaf->document_path) {
            Storage::disk('private')->delete($leaf->document_path);
        }

        $leaf->delete();

        return back()->with('success', 'Votre demande de congé a été annulée.');
    }

    public function downloadDocument(Leave $leaf)
    {
        $user = Auth::user();
        // Only the owner, Director, or Secretary can download the document
        if ($user->id !== $leaf->user_id && !$user->hasRole('Directeur') && !$user->hasRole('Secrétaire')) {
            abort(403, 'Accès non autorisé au document.');
        }

        if (!$leaf->document_path || !Storage::disk('private')->exists($leaf->document_path)) {
            abort(404, 'Document introuvable.');
        }

        return Storage::disk('private')->response($leaf->document_path);
    }

    private function checkOverlap(array $validated, $excludeLeaveId = null): ?string
    {
        $startDate = $validated['date_debut'];
        $endDate = $validated['date_fin'];

        $overlapping = Leave::where('user_id', Auth::id())
            ->whereIn('status', ['approuve', 'en_attente'])
            ->when($excludeLeaveId, function($query) use ($excludeLeaveId) {
                return $query->where('id', '!=', $excludeLeaveId);
            })
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('date_debut', [$startDate, $endDate])
                      ->orWhereBetween('date_fin', [$startDate, $endDate])
                      ->orWhere(function($query) use ($startDate, $endDate) {
                          $query->where('date_debut', '<=', $startDate)
                                ->where('date_fin', '>=', $endDate);
                      });
            })
            ->exists();

        if ($overlapping) {
            return 'Les dates de ce congé se chevauchent avec une autre demande existante.';
        }

        return null;
    }

    private function checkLeaveLimit(array $validated, $excludeLeaveId = null): ?string
    {
        $requestedDays = Carbon::parse($validated['date_debut'])->diffInDays(Carbon::parse($validated['date_fin'])) + 1;

        if ($validated['type'] === 'Maternité') {
            if ($requestedDays > 98) {
                return "Le congé de maternité est limité à 98 jours au total (ex: 6 semaines prénatal + 8 semaines postnatal).";
            }
            return null;
        }

        if ($validated['type'] === 'Maladie') {
            return null;
        }

        $currentYear = Carbon::parse($validated['date_debut'])->year;

        $query = Leave::where('user_id', Auth::id())
            ->whereYear('date_debut', $currentYear)
            ->whereNotIn('type', ['Maternité', 'Maladie'])
            ->whereIn('status', ['approuve', 'en_attente']);

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $previousLeaves = $query->get();

        $accumulatedDays = 0;
        foreach ($previousLeaves as $l) {
            $accumulatedDays += Carbon::parse($l->date_debut)->diffInDays(Carbon::parse($l->date_fin)) + 1;
        }

        if (($accumulatedDays + $requestedDays) > 30) {
            $remaining = max(0, 30 - $accumulatedDays);
            return "Le cumul de vos congés (hors maternité et maladie) ne peut dépasser 30 jours par an. Il vous reste $remaining jour(s) de disponible(s).";
        }

        return null;
    }
}
