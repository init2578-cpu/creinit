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

class LeaveController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        
        if ($user->hasRole('Directeur') || $user->hasRole('Secrétaire')) {
            $leaves = Leave::with('user')->orderBy('created_at', 'desc')->get();
        } else {
            $leaves = Leave::with('user')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        }

        return Inertia::render('Leaves/Index', [
            'leaves' => $leaves,
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

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leaves_documents', 'private');
        }

        Leave::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'motif' => $validated['motif'],
            'status' => 'en_attente',
            'document_path' => $documentPath,
        ]);

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
}
