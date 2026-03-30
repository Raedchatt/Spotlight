<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Enums\StatutEvenement;
use Illuminate\Http\Request;
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
}
