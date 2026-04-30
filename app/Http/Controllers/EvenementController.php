<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\EventCollaborator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\StatutEvenement;
use App\Enums\CategorieEvenement;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TypeMedia;
use App\Enums\TypeNotification;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use App\Services\NotificationService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Models\Paiement;
use App\Enums\StatutReservation;
use App\Enums\StatutPaiement;
use Stripe\Stripe;
use Stripe\Refund;

class EvenementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // List Events
    public function index()
    {
        $events = Evenement::with('medias')
            ->where('statut', StatutEvenement::Ouvert)
            ->latest()
            ->get();

        return response()->json($events);
    }

    // Show Event Details (Public)
    public function show(Request $request, $id)
    {
        $event = Evenement::with(['organisateur.organisateur', 'medias', 'collaborateurs.organizer.organisateur', 'tournoi'])->findOrFail($id);


        // Stats calculation
        $stats = [];
        if (!$event->is_tournoi) {
            $totalReserved = (int) $event->reservations()
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');
            $stats = [
                'total_reserved' => $totalReserved,
                'remaining' => max(0, $event->capacite_spectateur - $totalReserved),
                'total_revenue' => (float) ($totalReserved * $event->prix_spectateur * 0.8),
            ];
        } else {
            $participantReserved = (int) $event->reservations()
                ->where('ticket_type', '=', 'participant')
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');

            $spectatorReserved = (int) $event->reservations()
                ->where('ticket_type', '=', 'spectator')
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');

            $participantCapacity = ($event->type_tournoi === 'equipe' && $event->tournoi 
                    && $event->tournoi->nombre_equipes > 0 && $event->tournoi->joueurs_par_equipe > 0) 
                ? ($event->tournoi->nombre_equipes * $event->tournoi->joueurs_par_equipe) 
                : $event->capacite_participant;

            $stats = [
                'participant_reserved' => $participantReserved,
                'spectator_reserved' => $spectatorReserved,
                'participant_remaining' => max(0, $participantCapacity - $participantReserved),
                'spectator_remaining' => max(0, $event->capacite_spectateur - $spectatorReserved),
                'total_revenue' => (float) (($participantReserved * ($event->prix_participant ?? 0) * 0.8) + ($spectatorReserved * $event->prix_spectateur * 0.8)),
            ];
        }

        // Check if already reserved by auth user
        $isReserved = false;
        if (Auth::check()) {
            $isReserved = Reservation::where('evenement_id', '=', $id, 'and')
                ->where('user_id', '=', Auth::id(), 'and')
                ->whereIn('statut', ['confirmed', 'pending'])
                ->exists();
        }

        // Similar events (same category, different id, upcoming)
        $similarEvents = Evenement::with('medias')
            ->where('categorie', $event->categorie)
            ->where('id', '!=', $id)
            ->where('date_debut', '>=', now())
            ->take(3)
            ->get();

        // Check if user is a pending collaborator
        $isPendingCollaborator = false;
        $isAcceptedCollaborator = false;
        if (Auth::check()) {
            $collaboratorRecord = \App\Models\EventCollaborator::where('evenement_id', $id)
                ->where('organizer_id', Auth::id())
                ->first();

            if ($collaboratorRecord) {
                $isPendingCollaborator = $collaboratorRecord->statut === 'pending';
                $isAcceptedCollaborator = $collaboratorRecord->statut === 'accepted';
            }
        }

        if ($request->is('api/*') || ($request->wantsJson() && !$request->hasHeader('X-Inertia'))) {
            $event->loadCount('reservations');
            $totalTicketsReserved = (int) $event->reservations()
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');
            
            return response()->json(array_merge($event->toArray(), [
                'total_tickets_reserved' => $totalTicketsReserved,
                'medias' => $event->medias,
                'stats' => $stats,
                'is_reserved' => $isReserved,
                'is_pending_collaborator' => $isPendingCollaborator,
                'is_collaborator' => $isAcceptedCollaborator,
                'is_owner' => Auth::id() === $event->organisateur_id,
                'is_managed' => $event->isManagedBy(Auth::id()),
                'can_edit' => $event->isManagedBy(Auth::id(), 'can_edit'),
                'can_cancel' => $event->isManagedBy(Auth::id(), 'can_cancel'),
                'can_manage_team' => $event->isManagedBy(Auth::id(), 'can_manage_team'),
            ]));
        }

        return Inertia::render('Events/Show', [
            'event' => $event,
            'stats' => $stats,
            'is_reserved' => $isReserved,
            'is_pending_collaborator' => $isPendingCollaborator,
            'is_collaborator' => $isAcceptedCollaborator,
            'similar_events' => $similarEvents->isEmpty() ? null : $similarEvents,
        ]);
    }
    
    /**
     * Get management stats for an event.
     * Accessible only by owner and accepted co-organizers.
     */
    public function managementStats($id)
    {
        $event = Evenement::with(['collaborateurs.organizer', 'tournoi'])->findOrFail($id);

        if (!$event->isManagedBy(Auth::id())) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $stats = [];
        // ... (previous logic for stats calculation) ...
        // [Existing stats logic here, I'll use a larger replacement to be safe]
        if (!$event->is_tournoi) {
            $totalReserved = Reservation::where('evenement_id', '=', $id)
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');
            
            $totalRevenue = Reservation::where('evenement_id', '=', $id)
                ->where('statut', '=', 'confirmed')
                ->get()
                ->sum(fn($r) => $r->nombre_tickets * $event->prix_spectateur * 0.8);

            $stats = [
                'total_reserved' => (int) $totalReserved,
                'remaining' => max(0, $event->capacite_spectateur - $totalReserved),
                'total_revenue' => (float) $totalRevenue,
                'capacity' => $event->capacite_spectateur
            ];
        } else {
            $participantReserved = Reservation::where('evenement_id', '=', $id)
                ->where('ticket_type', '=', 'participant')
                ->where('statut', '=', 'confirmed')
                ->sum('nombre_tickets');

            $spectatorReserved = Reservation::where('evenement_id', '=', $id)
                ->where('ticket_type', '=', 'spectator')
                ->whereIn('statut', ['confirmed', 'pending'])
                ->sum('nombre_tickets');
            
            $participantRevenue = Reservation::where('evenement_id', '=', $id)
                ->where('ticket_type', '=', 'participant')
                ->where('statut', '=', 'confirmed')
                ->get()
                ->sum(fn($r) => $r->nombre_tickets * ($event->prix_participant ?? 0) * 0.8);
            
            $spectatorRevenue = Reservation::where('evenement_id', '=', $id)
                ->where('ticket_type', '=', 'spectator')
                ->where('statut', '=', 'confirmed')
                ->get()
                ->sum(fn($r) => $r->nombre_tickets * $event->prix_spectateur * 0.8);

            $participantCapacity = ($event->type_tournoi === 'equipe' && $event->tournoi 
                    && $event->tournoi->nombre_equipes > 0 && $event->tournoi->joueurs_par_equipe > 0) 
                ? ($event->tournoi->nombre_equipes * $event->tournoi->joueurs_par_equipe) 
                : $event->capacite_participant;

            $stats = [
                'participant_reserved' => (int) $participantReserved,
                'spectator_reserved' => (int) $spectatorReserved,
                'participant_remaining' => max(0, $participantCapacity - $participantReserved),
                'spectator_remaining' => max(0, $event->capacite_spectateur - $spectatorReserved),
                'total_revenue' => (float) ($participantRevenue + $spectatorRevenue),
                'participant_capacity' => $participantCapacity,
                'spectator_capacity' => $event->capacite_spectateur
            ];
        }

        return response()->json([
            'event' => [
                'id' => $event->id,
                'titre' => $event->titre,
                'is_tournoi' => $event->is_tournoi,
                'organisateur_id' => $event->organisateur_id,
            ],
            'user_permissions' => [
                'can_edit' => $event->isManagedBy(Auth::id(), 'can_edit'),
                'can_cancel' => $event->isManagedBy(Auth::id(), 'can_cancel'),
                'can_manage_team' => $event->isManagedBy(Auth::id(), 'can_manage_team'),
                'is_owner' => Auth::id() === $event->organisateur_id,
            ],
            'stats' => $stats,
            'collaborators' => $event->collaborateurs->map(fn($c) => [
                'id' => $c->id,
                'statut' => $c->statut,
                'can_edit' => $c->can_edit,
                'can_cancel' => $c->can_cancel,
                'can_manage_team' => $c->can_manage_team,
                'user' => [
                    'id' => $c->organizer->id,
                    'username' => $c->organizer->username,
                    'email' => $c->organizer->email,
                ]
            ])
        ]);
    }

    // Create Event
    public function store(Request $request)
    {
        $validationRules = [
            'titre' => 'required|string|max:255',
            'description' => 'required',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'lieu' => 'required|string',
            'prix_spectateur' => 'required|numeric',
            'capacite_spectateur' => 'required|integer',
            'categorie' => ['required', new Enum(CategorieEvenement::class)],
            'categorie_autre' => 'required_if:categorie,autre|nullable|string|max:255',
        ];

        $user = Auth::user();

        // Admin can create events on behalf of an organizer
        if ($user->isAdmin()) {
            $validationRules['organisateur_id'] = 'required|exists:users,id';
        }

        $request->validate($validationRules);

        // For non-admin organizers, check Stripe
        if (!$user->isAdmin() && $user->role === \App\Enums\Role::Organisateur) {
            $hasStripe = \App\Models\Organisateur::where('user_id', $user->id)
                ->whereNotNull('stripe_account_id')
                ->exists();

            if (!$hasStripe) {
                return response()->json([
                    'message' => 'Stripe account connection is required to create events. Please link your Stripe account in settings first.',
                    'require_stripe' => true
                ], 422);
            }
        }

        // Determine the organizer: admin can specify, others use their own ID
        $organisateurId = $user->isAdmin() ? $request->input('organisateur_id') : Auth::id();

        $event = Evenement::create([
            'organisateur_id' => $organisateurId,
            'titre' => $request->input('titre'),
            'description' => $request->input('description'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'lieu' => $request->input('lieu'),
            'prix_spectateur' => $request->input('prix_spectateur'),
            'capacite_spectateur' => $request->input('capacite_spectateur'),
            'categorie' => $request->input('categorie'),
            'categorie_autre' => $request->input('categorie') === 'autre' ? $request->input('categorie_autre') : null,
            // Tournament fields
            'is_tournoi' => $request->has('is_tournoi') ? $request->boolean('is_tournoi') : false,
            'type_tournoi' => $request->type_tournoi ?? null,
            'prix_participant' => $request->prix_participant ?? null,
            'capacite_participant' => $request->capacite_participant ?? null,
            'statut' => StatutEvenement::EnAttente
        ]);

        if ($event->is_tournoi) {
            $event->tournoi()->create([
                'prix_participant' => $request->prix_participant ?? 0,
                'capacite_participant' => $request->capacite_participant ?? 0,
                'type_tournoi' => $request->type_tournoi,
                'nombre_equipes' => $request->nombre_equipes ?? null,
                'joueurs_par_equipe' => $request->joueurs_par_equipe ?? null,
            ]);
        }

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $file) {
                // Determine type based on mime type
                $mimeType = $file->getMimeType();
                $type = str_starts_with($mimeType, 'video/') ? TypeMedia::Video : TypeMedia::Image;

                // Store the file
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'events/media',
                    'resource_type' => $type === TypeMedia::Video ? 'video' : 'image'
                ]);

                $url = $uploadResult['secure_url'];

                // Create media record
                $event->medias()->create([
                    'url' => $url,
                    'type' => $type,
                ]);

                // Also set as main poster if it's an image and not set yet
                if ($type === TypeMedia::Image && !$event->poster_url) {
                    $event->update(['poster_url' => $url]);
                }
            }
        }

        // Handle pre-uploaded AI media URLs
        if ($request->has('ai_media_urls')) {
            $urls = $request->input('ai_media_urls');
            $urlArray = is_array($urls) ? $urls : (is_string($urls) ? [$urls] : []);
            
            foreach ($urlArray as $url) {
                $event->medias()->create([
                    'url' => $url,
                    'type' => TypeMedia::Image,
                ]);

                // Set as poster if not set
                if (!$event->poster_url) {
                    $event->update(['poster_url' => $url]);
                }
            }
        }

        // Notify all participants about the new event
        $this->notificationService->notifieParticipantsNouvelEvenement(
            $event->titre,
            $event->date_debut->format('M d, Y'),
            $event->lieu,
            $event->id
        );

        // Notify Admins
        $this->notificationService->notifieAdminsEvenementEnAttente(
            $event->titre,
            $user->username
        );

        // Attach collaborators if any are provided
        if ($request->has('collaborator_ids')) {
            $collabIds = $request->input('collaborator_ids');
            $collabIdsArray = is_array($collabIds) ? $collabIds : [$collabIds];

            foreach ($collabIdsArray as $orgId) {
                // Ensure the user actually exists and is an organizer
                $orgUser = \App\Models\User::where('id', $orgId)
                                         ->where('role', \App\Enums\Role::Organisateur)
                                         ->first();
                if ($orgUser) {
                    $event->collaborateurs()->create([
                        'organizer_id' => $orgId,
                        'statut' => 'pending'
                    ]);

                    // Send notification to the invited organizer
                    app(\App\Services\NotificationService::class)->envoyerInvitationCollaboration(
                        (int) $orgId,
                        $event->titre,
                        $user->username,
                        $event->id
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event->load('medias')
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        if (!$event->isManagedBy(Auth::id(), 'can_edit')) {
            return response()->json(['message' => 'Unauthorized action. You need edit permissions for this event.'], 403);
        }

        // Validate input (including tournament fields)
        try {
            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'required',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'lieu' => 'required|string',
                'prix_spectateur' => 'required|numeric',
                'capacite_spectateur' => 'required|integer',
                'categorie' => ['required', new Enum(CategorieEvenement::class)],
                'categorie_autre' => 'required_if:categorie,autre|nullable|string|max:255',
                'statut' => ['required', new Enum(StatutEvenement::class)],
                'is_tournoi' => 'sometimes|boolean',
                'type_tournoi' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|in:equipe,individuel',
                'prix_participant' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|numeric|min:0',
                'capacite_participant' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|integer|min:1',
                'medias.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', // Allow up to 20MB
                'media_to_delete' => 'nullable|array',
                'media_to_delete.*' => 'integer|exists:media,id',
                'poster_url' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Event Update Validation Failed:', $e->errors());
            throw $e;
        }

        $event->update([
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'lieu' => $request->lieu,
            'prix_spectateur' => $request->prix_spectateur,
            'capacite_spectateur' => $request->capacite_spectateur,
            'categorie' => $request->categorie,
            'categorie_autre' => $request->categorie === 'autre' ? $request->categorie_autre : null,
            'statut' => $request->statut,
            // Tournament fields
            'is_tournoi' => $request->has('is_tournoi') ? $request->boolean('is_tournoi') : false,
            'type_tournoi' => $request->type_tournoi ?? null,
            'prix_participant' => $request->prix_participant ?? null,
            'capacite_participant' => $request->capacite_participant ?? null,
            'poster_url' => $request->poster_url ?? $event->poster_url,
        ]);

        if ($event->is_tournoi) {
            $event->tournoi()->updateOrCreate(
                ['evenement_id' => $event->id],
                [
                    'prix_participant' => $request->prix_participant ?? 0,
                    'capacite_participant' => $request->capacite_participant ?? 0,
                    'type_tournoi' => $request->type_tournoi,
                    'nombre_equipes' => $request->nombre_equipes ?? null,
                    'joueurs_par_equipe' => $request->joueurs_par_equipe ?? null,
                ]
            );
        } else {
            $event->tournoi()->delete();
        }

        // Handle media deletion
        if ($request->has('media_to_delete')) {
            $mediaIds = $request->input('media_to_delete');
            $medias = $event->medias()->whereIn('id', $mediaIds)->get();

            foreach ($medias as $media) {
                // If it's the poster, clear it
                if ($event->poster_url === $media->url) {
                    $event->update(['poster_url' => null]);
                }

                // Try to delete from Cloudinary if possible (needs public_id storage, which we don't have yet, so just delete DB record)
                // For now, we just delete the database record
                $media->delete();
            }
        }

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $file) {
                // Determine type based on mime type
                $mimeType = $file->getMimeType();
                $type = str_starts_with($mimeType, 'video/') ? TypeMedia::Video : TypeMedia::Image;

                // Store the file
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'events/media',
                    'resource_type' => $type === TypeMedia::Video ? 'video' : 'image'
                ]);

                $url = $uploadResult['secure_url'];

                // Create media record
                $event->medias()->create([
                    'url' => $url,
                    'type' => $type,
                ]);

                // Also set as main poster if it's an image and not set yet
                if ($type === TypeMedia::Image && !$event->poster_url) {
                    $event->update(['poster_url' => $url]);
                }
            }
        }

        // Notify all participants about the event update
        $this->notificationService->notifieParticipantsEvenementModifie($event->titre, $event->id);

        // Notify Admins
        $this->notificationService->notifieAdminsEvenementModifie(
            $event->titre,
            Auth::user()->username
        );

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event->load('medias')
        ]);
    }

    // Cancel Event with Refunds
    public function cancelWithRefund($id)
    {
        $event = Evenement::findOrFail($id);

        // Permissions check: Only owner or collaborator with 'can_cancel'
        if (!$event->isManagedBy(Auth::id(), 'can_cancel')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to cancel this event.'], 403);
        }

        if ($event->statut === StatutEvenement::Annule) {
            return response()->json(['message' => 'Event is already cancelled.'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $reservations = Reservation::where('evenement_id', '=', $id, 'and')
            ->where('statut', '=', StatutReservation::Confirmed->value, 'and')
            ->get();

        $refundedCount = 0;
        $failedCount = 0;

        foreach ($reservations as $reservation) {
            $paiement = Paiement::where('reservation_id', '=', $reservation->id, 'and')
                ->where('statut', '=', StatutPaiement::Succeeded->value, 'and')
                ->first();

            if ($paiement && $paiement->stripe_payment_intent_id) {
                try {
                    Refund::create([
                        'payment_intent' => $paiement->stripe_payment_intent_id,
                    ]);

                    $paiement->update(['statut' => StatutPaiement::Refunded]);
                    $refundedCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Refund failed for reservation {$reservation->id}: " . $e->getMessage());
                    $failedCount++;
                    // Even if refund fails in Stripe (e.g. key issue), we proceed with status updates for record keeping
                }
            }
            
            $reservation->update(['statut' => StatutReservation::Cancelled]);
        }

        $event->update(['statut' => StatutEvenement::Annule]);

        // Notify all participants about the event cancellation
        $this->notificationService->notifieParticipantsEvenementAnnule($event->titre, $event->id);

        // Notify Admins
        $this->notificationService->notifieAdminsEvenementSupprime(
            $event->titre,
            Auth::user()->username
        );

        return response()->json([
            'message' => 'Event cancelled successfully.',
            'refunded_count' => $refundedCount,
            'failed_count' => $failedCount,
            'status' => 'cancelled'
        ]);
    }

    // Legacy delete (keeping signature for internal consistency)
    public function destroy($id)
    {
        return $this->cancelWithRefund($id);
    }

    // Search events
    public function search(Request $request)
    {
        $query = Evenement::with('medias')
            ->withCount('reservations')
            ->withSum(['reservations as total_tickets_reserved' => function($q) {
                $q->whereIn('statut', ['confirmed', 'pending']);
            }], 'nombre_tickets');

        if ($request->has('organisateur_id')) {
            $query->parOrganisateur($request->input('organisateur_id'));
        }

        if ($request->has('titre')) {
            $query->parTitre($request->input('titre'));
        }

        if ($request->has('date')) {
            $query->parDate($request->input('date'));
        }

        if ($request->has('prix_max')) {
            $query->parPrix($request->input('prix_max'));
        }

        if ($request->has('categorie')) {
            $query->parCategorie($request->input('categorie'));
        }

        if ($request->has('statut')) {
            $query->parStatut($request->input('statut'));
        }

        $limit = $request->input('limit');
        $perPage = $request->input('per_page');

        if ($limit) {
            $events = $query->latest()->limit($limit)->get();
        } elseif ($perPage) {
            $events = $query->latest()->paginate($perPage);
        } else {
            $events = $query->latest()->get();
        }

        // Add is_reserved flag for authenticated users
        if (Auth::check()) {
            $userId = Auth::id();
            $userReservations = Reservation::where('user_id', '=', $userId, 'and')
                ->whereIn('statut', ['confirmed', 'pending'])
                ->pluck('evenement_id')
                ->toArray();

            $items = ($perPage && $events instanceof \Illuminate\Pagination\LengthAwarePaginator) 
                ? $events->getCollection() 
                : $events;

            foreach ($items as $event) {
                $event->is_reserved = in_array($event->id, $userReservations);
            }

            if ($perPage && $events instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $events->setCollection($items);
            }
        }

        return response()->json($events);
    }

    // Open reservation
    public function ouvrirReservation($id)
    {
        $event = Evenement::findOrFail($id);
        $event->ouvrirReservation();

        return response()->json([
            'message' => 'Reservations opened successfully',
            'event' => $event
        ]);
    }

    // Close reservation
    public function fermerReservation($id)
    {
        $event = Evenement::findOrFail($id);
        $event->fermerReservation();

        return response()->json([
            'message' => 'Reservations closed successfully',
            'event' => $event
        ]);
    }
    // Toggle collaborator permission
    public function toggleCollaboratorPermission(Request $request, $id, $collaboratorId)
    {
        $event = Evenement::findOrFail($id);

        // Only owner can manage permissions
        if ($event->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. Only the owner can manage team permissions.'], 403);
        }

        $collaborator = EventCollaborator::where('evenement_id', $id)
            ->where('id', $collaboratorId)
            ->firstOrFail();

        $permission = $request->input('permission');
        $value = $request->boolean('value');

        if (!in_array($permission, ['can_edit', 'can_cancel', 'can_manage_team'])) {
            return response()->json(['message' => 'Invalid permission.'], 400);
        }

        $collaborator->update([
            $permission => $value
        ]);

        return response()->json([
            'message' => 'Permission updated successfully.',
            'collaborator' => $collaborator
        ]);
    }
}