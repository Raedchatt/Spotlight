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
use Inertia\Inertia;
use App\Services\NotificationService;
// use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class OrganisateurController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
            'total' => $organisateurs->count(),
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
            'evenements' => fn($q) => $q->latest()->take(10),
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
            $logoPath = cloudinary()->uploadApi()->upload($request->file('logo')->getRealPath(), [
                'folder' => 'organisateurs/logos'
            ])['secure_url'];
        }

        $organisateur = Organisateur::create([
            'user_id' => Auth::id(),
            'nom_organisation' => $request->nom_organisation,
            'description' => $request->description,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'site_web' => $request->site_web,
            'logo' => $logoPath,
            'statut' => 'pending',
        ]);

        return response()->json([
            'message' => 'Your organizer application has been submitted and is pending review.',
            'organisateur' => $organisateur,
        ], 201);
    }

    /**
     * Update the authenticated user's organizer profile.
     *
     * PUT /api/organisateurs/{organisateur}
     */
    public function update(UpdateOrganisateurRequest $request, Organisateur $organisateur)
    {
        // Extra guard (FormRequest authorize() already checks, but belt-and-suspenders)
        if ($organisateur->user_id !== Auth::id()) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
            abort(403);
        }

        $data = $request->only([
            'nom_organisation',
            'description',
            'telephone',
            'adresse',
            'site_web',
        ]);

        // Handle logo replacement
        if ($request->hasFile('logo')) {
            // Remove old logo from Cloudinary if it exists (using Cloudinary facade would require extra parsing, so we skip deletion for now to keep it simple and safe)
            $data['logo'] = cloudinary()->uploadApi()->upload($request->file('logo')->getRealPath(), [
                'folder' => 'organisateurs/logos'
            ])['secure_url'];
        }

        $organisateur->update($data);

        if ($request->header('X-Inertia')) {
            return back()->with('message', 'Organizer profile updated successfully.');
        }

        return response()->json([
            'message' => 'Organizer profile updated successfully.',
            'organisateur' => $organisateur->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Admin Routes (auth:sanctum + IsAdmin)
    // -------------------------------------------------------------------------

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

        if ($user->role->value !== 'organisateur' || !$user->isApprovedOrganisateur()) {
            return response()->json(['message' => 'Unauthorized. Only approved organizers can create events.'], 403);
        }

        if (!$user->organisateur || !$user->organisateur->stripe_account_id) {
            return response()->json([
                'message' => 'Stripe account connection is required to create events. Please link your Stripe account in settings first.',
                'require_stripe' => true
            ], 422);
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

        // Notify all participants about the new event
        $this->notificationService->notifieParticipantsNouvelEvenement(
            $event->titre,
            $event->date_debut->format('M d, Y'),
            $event->lieu
        );

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

        // Notify all participants about the event update
        $this->notificationService->notifieParticipantsEvenementModifie($evenement->titre);

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

        // Notify all participants about the event cancellation
        $this->notificationService->notifieParticipantsEvenementAnnule($evenement->titre);

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

        $reservations = Reservation::whereHas('evenement', function ($q) use ($organisateurId) {
            $q->where('organisateur_id', $organisateurId);
        })->get();

        $totalReservations = $reservations->count();
        $confirmedReservations = $reservations->where('statut', \App\Enums\StatutReservation::Confirmed)->count();

        $totalRevenue = $reservations->where('statut', \App\Enums\StatutReservation::Confirmed)
            ->sum(function ($r) {
                return $r->nombre_tickets * (float) $r->evenement->prix_spectateur;
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

    /**
     * Return data for the organizer Inertia dashboard page.
     *
     * GET /dashboard  (web route, organizer only)
     */
    public function dashboardData()
    {
        $user = Auth::user();

        if (!$user || !$user->isOrganisateur()) {
            return Inertia::render('Dashboard', [
                'dashboardData' => null,
            ]);
        }

        $organisateurId = $user->id;

        // ------------------------------------
        // Revenue: confirmed vs pending
        // ------------------------------------
        $allReservations = Reservation::whereHas('evenement', function ($q) use ($organisateurId) {
            $q->where('organisateur_id', $organisateurId);
        })->with('evenement:id,prix_spectateur')->get();

        $totalReceived = $allReservations
            ->where('statut', \App\Enums\StatutReservation::Confirmed)
            ->sum(fn($r) => $r->nombre_tickets * (float) $r->evenement->prix_spectateur);

        $pendingPayout = $allReservations
            ->where('statut', \App\Enums\StatutReservation::Pending)
            ->sum(fn($r) => $r->nombre_tickets * (float) $r->evenement->prix_spectateur);

        // ------------------------------------
        // Category breakdown (% of events)
        // ------------------------------------
        $events = Evenement::where('organisateur_id', $organisateurId)->get();
        $totalEvents = $events->count();

        $categoryBreakdown = [];
        if ($totalEvents > 0) {
            $grouped = $events->groupBy(fn($e) => $e->categorie instanceof CategorieEvenement ? $e->categorie->value : $e->categorie);
            foreach ($grouped as $cat => $catEvents) {
                $categoryBreakdown[] = [
                    'category' => $cat,
                    'count'    => $catEvents->count(),
                    'percent'  => round(($catEvents->count() / $totalEvents) * 100),
                ];
            }
            usort($categoryBreakdown, fn($a, $b) => $b['count'] <=> $a['count']);
        }

        // ------------------------------------
        // Current / active events with revenue
        // ------------------------------------
        $currentEvents = Evenement::where('organisateur_id', $organisateurId)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($event) use ($allReservations) {
                $eventRevenue = $allReservations
                    ->where('evenement_id', $event->id)
                    ->where('statut', \App\Enums\StatutReservation::Confirmed)
                    ->sum(fn($r) => $r->nombre_tickets * (float) $event->prix_spectateur);

                return [
                    'id'       => $event->id,
                    'titre'    => $event->titre,
                    'categorie'=> $event->categorie instanceof CategorieEvenement ? $event->categorie->value : $event->categorie,
                    'lieu'     => $event->lieu,
                    'statut'   => $event->statut instanceof StatutEvenement ? $event->statut->value : $event->statut,
                    'revenue'  => round($eventRevenue, 2),
                ];
            });

        // ------------------------------------
        // Top 9 active participants
        // ------------------------------------
        $participantCounts = $allReservations
            ->whereIn('statut', [\App\Enums\StatutReservation::Confirmed->value, \App\Enums\StatutReservation::Pending->value])
            ->groupBy('user_id')
            ->map(fn($group) => [
                'user_id'     => $group->first()->user_id,
                'event_count' => $group->count(),
            ])
            ->sortByDesc('event_count')
            ->take(9)
            ->values();

        $participantUserIds = $participantCounts->pluck('user_id');
        $participantUsers = \App\Models\User::whereIn('id', $participantUserIds)
            ->get(['id', 'username'])
            ->keyBy('id');

        $activeParticipants = $participantCounts->map(function ($pc) use ($participantUsers) {
            $u = $participantUsers->get($pc['user_id']);
            return [
                'id'          => $pc['user_id'],
                'username'    => $u ? $u->username : 'Unknown',
                'avatar'      => null,
                'event_count' => $pc['event_count'],
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'totalReceived'      => round($totalReceived, 2),
                'pendingPayout'      => round($pendingPayout, 2),
                'categoryBreakdown'  => $categoryBreakdown,
                'currentEvents'      => $currentEvents,
                'activeParticipants' => $activeParticipants,
                'totalEvents'        => $totalEvents,
            ],
        ]);
    }
}
