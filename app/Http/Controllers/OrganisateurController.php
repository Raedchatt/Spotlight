<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganisateurRequest;
use App\Http\Requests\UpdateOrganisateurRequest;
use App\Models\Organisateur;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Enums\StatutEvenement;
use App\Enums\CategorieEvenement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class OrganisateurController extends Controller
{
    // -------------------------------------------------------------------------
    // Public Routes
    // -------------------------------------------------------------------------

    /**
     * List all approved organizers (publicly accessible).
     *
     * GET /api/organisateurs
     */
    public function index(): JsonResponse
    {
        $organisateurs = Organisateur::approved()
            ->with('user:id,username,email')
            ->latest()
            ->get();

        return response()->json([
            'organisateurs' => $organisateurs,
            'total'         => $organisateurs->count(),
        ]);
    }

    /**
     * Show a single approved organizer profile with their events (publicly accessible).
     *
     * GET /api/organisateurs/{organisateur}
     */
    public function show(Organisateur $organisateur): JsonResponse
    {
        // Only approved profiles are publicly visible
        if (!$organisateur->isApproved()) {
            return response()->json(['message' => 'Organizer profile not found.'], 404);
        }

        $organisateur->load([
            'user:id,username,email',
            'evenements' => fn ($q) => $q->latest()->take(10),
        ]);

        return response()->json($organisateur);
    }

    // -------------------------------------------------------------------------
    // Protected Routes (auth:sanctum)
    // -------------------------------------------------------------------------

    /**
     * Submit a request to become an organizer.
     * A user can only have one organizer profile.
     *
     * POST /api/organisateurs
     */
    public function store(StoreOrganisateurRequest $request): JsonResponse
    {
        // Prevent duplicate applications
        if (Auth::user()->organisateur) {
            return response()->json([
                'message' => 'You have already submitted an organizer application.',
            ], 422);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = Cloudinary::upload($request->file('logo')->getRealPath(), [
                'folder' => 'organisateurs/logos'
            ])->getSecurePath();
        }

        $organisateur = Organisateur::create([
            'user_id'          => Auth::id(),
            'nom_organisation' => $request->nom_organisation,
            'description'      => $request->description,
            'telephone'        => $request->telephone,
            'adresse'          => $request->adresse,
            'site_web'         => $request->site_web,
            'logo'             => $logoPath,
            'statut'           => 'pending',
        ]);

        return response()->json([
            'message'       => 'Your organizer application has been submitted and is pending review.',
            'organisateur'  => $organisateur,
        ], 201);
    }

    /**
     * Update the authenticated user's organizer profile.
     *
     * PUT /api/organisateurs/{organisateur}
     */
    public function update(UpdateOrganisateurRequest $request, Organisateur $organisateur): JsonResponse
    {
        // Extra guard (FormRequest authorize() already checks, but belt-and-suspenders)
        if ($organisateur->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $data = $request->only([
            'nom_organisation', 'description', 'telephone', 'adresse', 'site_web',
        ]);

        // Handle logo replacement
        if ($request->hasFile('logo')) {
            // Remove old logo from Cloudinary if it exists (using Cloudinary facade would require extra parsing, so we skip deletion for now to keep it simple and safe)
            $data['logo'] = Cloudinary::upload($request->file('logo')->getRealPath(), [
                'folder' => 'organisateurs/logos'
            ])->getSecurePath();
        }

        $organisateur->update($data);

        return response()->json([
            'message'      => 'Organizer profile updated successfully.',
            'organisateur' => $organisateur->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Admin Routes (auth:sanctum + IsAdmin)
    // -------------------------------------------------------------------------

    /**
     * List all pending organizer applications (admin only).
     *
     * GET /api/admin/organisateurs/pending
     */
    public function pending(): JsonResponse
    {
        $organisateurs = Organisateur::pending()
            ->with('user:id,username,email')
            ->latest()
            ->get();

        return response()->json([
            'organisateurs' => $organisateurs,
            'total'         => $organisateurs->count(),
        ]);
    }
    // -------------------------------------------------------------------------
    // Event Management (Organizer Level)
    // -------------------------------------------------------------------------

    /**
     * Create a new event as an organizer.
     *
     * POST /api/organisateurs/events
     */
    public function creerEvenement(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== 'organisateur' || !$user->isApprovedOrganisateur()) {
            return response()->json(['message' => 'Unauthorized. Only approved organizers can create events.'], 403);
        }

        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required',
            'date_debut' => 'required|date|after:now',
            'date_fin' => 'required|date|after:date_debut',
            'lieu' => 'required|string',
            'prix_spectateur' => 'required|numeric|min:0',
            'capacite_spectateur' => 'required|integer|min:1',
            'categorie' => ['required', new Enum(CategorieEvenement::class)],
        ]);

        $event = Evenement::create([
            'organisateur_id' => $user->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'lieu' => $request->lieu,
            'prix_spectateur' => $request->prix_spectateur,
            'capacite_spectateur' => $request->capacite_spectateur,
            'categorie' => $request->categorie,
            'statut' => StatutEvenement::Ouvert, // Organizer events start as open by default
        ]);

        return response()->json([
            'message' => 'Event created successfully and is awaiting review.',
            'event' => $event
        ], 201);
    }

    /**
     * Update an event owned by the organizer.
     *
     * PUT /api/organisateurs/events/{evenement}
     */
    public function modifierEvenement(Request $request, Evenement $evenement): JsonResponse
    {
        if ($evenement->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action. You do not own this event.'], 403);
        }

        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'lieu' => 'sometimes|required|string',
            'prix_spectateur' => 'sometimes|required|numeric|min:0',
            'capacite_spectateur' => 'sometimes|required|integer|min:1',
            'categorie' => ['sometimes', 'required', new Enum(CategorieEvenement::class)],
        ]);

        $evenement->update($request->all());

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $evenement->fresh()
        ]);
    }

    /**
     * Cancel an event owned by the organizer.
     *
     * PATCH /api/organisateurs/events/{evenement}/annuler
     */
    public function annulerEvenement(Evenement $evenement): JsonResponse
    {
        if ($evenement->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        if ($evenement->statut === StatutEvenement::Annule) {
            return response()->json(['message' => 'Event is already cancelled.'], 422);
        }

        $evenement->update(['statut' => StatutEvenement::Annule]);

        return response()->json([
            'message' => 'Event cancelled successfully.',
            'event' => $evenement->fresh()
        ]);
    }

    /**
     * View statistics for the authenticated organizer.
     *
     * GET /api/organisateurs/stats
     */
    public function consulterStatistiques(): JsonResponse
    {
        $organisateurId = Auth::id();
        
        $totalEvents = Evenement::where('organisateur_id', $organisateurId)->count();
        $activeEvents = Evenement::where('organisateur_id', $organisateurId)
            ->where('statut', StatutEvenement::Ouvert)
            ->count();
            
        $reservations = Reservation::whereHas('evenement', function($q) use ($organisateurId) {
            $q->where('organisateur_id', $organisateurId);
        })->get();

        $totalReservations = $reservations->count();
        $confirmedReservations = $reservations->where('statut', \App\Enums\StatutReservation::Confirmed)->count();
        
        $totalRevenue = $reservations->where('statut', \App\Enums\StatutReservation::Confirmed)
            ->sum(function($r) {
                return $r->nombre_tickets * (float)$r->evenement->prix_spectateur;
            });

        return response()->json([
            'stats' => [
                'total_events' => $totalEvents,
                'active_events' => $activeEvents,
                'total_reservations' => $totalReservations,
                'confirmed_reservations' => $confirmedReservations,
                'total_revenue' => round($totalRevenue, 2),
                'currency' => 'TND'
            ]
        ]);
    }
}
