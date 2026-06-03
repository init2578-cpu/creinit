<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact', [
            'settings' => Setting::getGroup('general')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        ContactMessage::create($request->only(['name', 'email', 'subject', 'message']));
        
        return back()->with('success', 'Message reçu ! Notre réseau de neurones traite votre demande.');
    }

    public function adminIndex()
    {
        return Inertia::render('Admin/ContactMessages', [
            'messages' => ContactMessage::latest()->get()
        ]);
    }

    public function adminDestroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();
        return back()->with('success', 'Message supprimé avec succès.');
    }

    public function markAsRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => true]);
        return back();
    }
}
