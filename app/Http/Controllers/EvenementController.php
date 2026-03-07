<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvenementController extends Controller
{
    // List Events
    public function index()
    {
        $events = Evenement::latest()->get();

        return response()->json($events);
    }

    // Create Event
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'lieu' => 'required|string',
            'prix_spectateur' => 'required|numeric',
            'capacite_spectateur' => 'required|integer',
            'categorie' => 'required'
        ]);

        $event = Evenement::create([
            'organisateur_id' => Auth::id(),
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'lieu' => $request->lieu,
            'prix_spectateur' => $request->prix_spectateur,
            'capacite_spectateur' => $request->capacite_spectateur,
            'categorie' => $request->categorie,
            'statut' => 'en_attente'
        ]);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event
        ]);
    }

    // Update Event
    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        $event->update($request->all());

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event
        ]);
    }

    // Delete Event
    public function destroy($id)
    {
        $event = Evenement::findOrFail($id);

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }
}