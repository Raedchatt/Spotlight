<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Evenement;
use App\Models\User;
use App\Enums\StatutEvenement;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminEventController extends Controller
{
    /**
     * Display a listing of pending events.
     */
    public function index()
    {
        $events = Evenement::with('organisateur')
            ->where('statut', StatutEvenement::EnAttente)
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events
        ]);
    }

    /**
     * Display a listing of all events with filters.
     */
    public function allEvents(Request $request)
    {
        $query = Evenement::with('organisateur');

        if ($request->filled('search')) {
            $query->where('titre', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $cat = $request->category;
            // Check if it's one of the main enum types (sportifs, culturels, etc)
            $mainCategories = ['sportifs', 'culturels', 'scientifiques', 'musicaux', 'commerciaux', 'autre'];
            if (in_array(strtolower($cat), $mainCategories)) {
                $query->where('categorie', $cat);
            } else {
                // Find events where categorie is 'autre' and categorie_autre matches
                $query->where('categorie', 'autre')
                      ->where('categorie_autre', 'like', '%' . $cat . '%');
            }
        }

        if ($request->filled('organizer')) {
            $query->whereHas('organisateur', function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->organizer . '%');
            });
        }

        $events = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Events/AllEvents', [
            'events' => $events,
            'filters' => $request->only(['search', 'category', 'organizer']),
            'categories' => Category::all()
        ]);
    }

    /**
     * Search organizers by username or email.
     */
    public function searchOrganizers(Request $request)
    {
        $query = $request->input('q', '');

        $organizers = User::where('role', Role::Organisateur)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'username', 'email']);

        return response()->json($organizers);
    }

    /**
     * Approve the specified event.
     */
    public function approve(Evenement $event)
    {
        // Only approve if it's currently pending
        if ($event->statut !== StatutEvenement::EnAttente) {
            return back()->with('error', 'Event is not in pending status.');
        }

        $event->update([
            'statut' => StatutEvenement::Ouvert
        ]);

        app(\App\Services\NotificationService::class)->notifieOrganisateurEvenementApprouve($event->organisateur_id, $event->titre);

        return back()->with('success', 'Event approved successfully.');
    }

    /**
     * Reject the specified event.
     */
    public function reject(Evenement $event)
    {
        // Only reject if it's currently pending
        if ($event->statut !== StatutEvenement::EnAttente) {
            return back()->with('error', 'Event is not in pending status.');
        }

        $event->update([
            'statut' => StatutEvenement::Rejete
        ]);

        app(\App\Services\NotificationService::class)->notifieOrganisateurEvenementRejete($event->organisateur_id, $event->titre);

        return back()->with('success', 'Event rejected successfully.');
    }

    /**
     * Deletes (cancels) the specified event.
     */
    public function destroy(Evenement $event)
    {
        // For simplicity and safety, we use the same cancel logic if there are reservations
        // but as an Admin, we might eventually want a strict delete.
        // For now, we'll use a controller-to-controller call or just replicate the safe cancellation logic.
        
        $controller = app(\App\Http\Controllers\EvenementController::class);
        $result = $controller->cancelWithRefund($event->id);

        return back()->with('success', 'Event deleted and cancelled successfully.');
    }
}
