<?php

namespace App\Http\Controllers;

use App\Enums\StatutReservation;
use App\Enums\TypeNotification;
use App\Models\Evenement;
use App\Models\Notification;
use App\Models\Reservation;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Store a new reservation.
     *
     * POST /api/reservations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'evenement_id' => 'required|exists:evenements,id',
            'nombre_tickets' => 'required|integer|min:1',
            'ticket_type' => 'nullable|string|in:standard,spectator,participant',
            'note' => 'nullable|string|max:500',
        ]);

        $evenement = Evenement::findOrFail($validated['evenement_id']);

        // 0. Only participants can reserve events
        if (Auth::user()->role !== \App\Enums\Role::Participant) {
            return response()->json([
                'message' => 'Only participants can reserve events. Organizers are not allowed to make reservations.',
            ], 403);
        }

        // Set default ticket type if not provided
        $ticketType = $validated['ticket_type'] ?? 'standard';
        if ($evenement->is_tournoi && $ticketType === 'standard') {
            $ticketType = 'spectator'; // Default for tournaments if not specified
        }

        // 0.1 Check if user already has a reservation for this event
        $existingReservation = Reservation::where('user_id', '=', Auth::id(), 'and')
            ->where('evenement_id', '=', $evenement->id, 'and')
            ->first();

        if ($existingReservation) {
            if ($existingReservation->statut === StatutReservation::Confirmed) {
                return response()->json([
                    'message' => 'You have already confirmed your reservation for this event.',
                ], 422);
            }
            
            // If it's pending and paid, we update the quantity and type then "re-initiate" the payment flow
            $unitPrice = (float) $evenement->prix_spectateur;
            if ($evenement->is_tournoi && $ticketType === 'participant') {
                $unitPrice = (float) $evenement->prix_participant;
            }

            if ($unitPrice > 0) {
                // Update with new choice before payment
                $existingReservation->update([
                    'nombre_tickets' => $validated['nombre_tickets'],
                    'ticket_type'    => $ticketType,
                ]);

                $stripeController = app(\App\Http\Controllers\StripeController::class);
                $response = $stripeController->createCheckoutSession($request, $existingReservation);
                $data = json_decode($response->getContent(), true);

                if (isset($data['checkout_url'])) {
                    return response()->json([
                        'message' => 'Updating reservation and redirecting to payment...',
                        'checkout_url' => $data['checkout_url'],
                        'reservation' => $existingReservation->load('evenement'),
                    ], 201);
                }
            }

            return response()->json([
                'message' => 'You already have a pending reservation for this event.',
                'reservation' => $existingReservation,
            ], 422);
        }

        // 1. Check if the event is open for reservations
        if ($evenement->statut->value !== 'ouvert') {
            return response()->json([
                'message' => 'This event is not open for reservations.',
            ], 422);
        }

        // 2. Check capacity based on ticket type
        if ($evenement->is_tournoi && $ticketType === 'participant') {
            $currentReservations = $evenement->reservations()
                ->active()
                ->where('ticket_type', 'participant')
                ->sum('nombre_tickets');
            $availableSpaces = $evenement->capacite_participant - $currentReservations;
        } else {
            $currentReservations = $evenement->reservations()
                ->active()
                ->where(function ($query) {
                    $query->where('ticket_type', 'spectator')
                        ->orWhere('ticket_type', 'standard')
                        ->orWhereNull('ticket_type');
                })
                ->sum('nombre_tickets');
            $availableSpaces = $evenement->capacite_spectateur - $currentReservations;
        }

        if ($validated['nombre_tickets'] > $availableSpaces) {
            $typeLabel = $ticketType === 'participant' ? 'participant' : 'spectator';
            return response()->json([
                'message' => "Not enough {$typeLabel} spaces available. Only {$availableSpaces} spots left.",
            ], 422);
        }

        // 3. Create the reservation
        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'evenement_id' => $evenement->id,
            'ticket_type' => $ticketType,
            'nombre_tickets' => $validated['nombre_tickets'],
            'note' => $validated['note'] ?? null,
            'statut' => StatutReservation::Pending,
            'revendeur_id' => ($code = $request->cookie('referral_code')) 
                ? \App\Models\Revendeur::where('referral_code', $code)->first()?->id 
                : null,
        ]);

        // 4. Handle Payment if necessary
        $unitPrice = (float) $evenement->prix_spectateur;
        if ($evenement->is_tournoi && $ticketType === 'participant') {
            $unitPrice = (float) $evenement->prix_participant;
        }

        $totalAmount = $unitPrice * $validated['nombre_tickets'];

        \Illuminate\Support\Facades\Log::info("Reservation Attempt", [
            'event_id' => $evenement->id,
            'unit_price' => $unitPrice,
            'tickets' => $validated['nombre_tickets'],
            'total' => $totalAmount,
            'ticket_type' => $ticketType
        ]);

        if ($totalAmount > 0) {
            // Initiate Stripe Checkout
            $stripeController = app(\App\Http\Controllers\StripeController::class);
            $response = $stripeController->createCheckoutSession($request, $reservation);
            
            $data = json_decode($response->getContent(), true);

            if (isset($data['checkout_url'])) {
                return response()->json([
                    'message' => 'Reservation created. Redirecting to payment...',
                    'checkout_url' => $data['checkout_url'],
                    'reservation' => $reservation->load('evenement'),
                ], 201);
            }

            // If we are here, payment initialization failed!
            // We should return an error instead of letting the reservation "pass"
            return response()->json([
                'message' => 'Payment initialization failed. Please try again.',
                'error' => $data['error'] ?? 'Stripe session could not be created.',
                'reservation' => $reservation->load('evenement'),
            ], 500);
        }

        // Auto-confirm free events — notify organizer right away
        $reservation->update(['statut' => StatutReservation::Confirmed]);

        $user = Auth::user();
        $this->notificationService->notifieOrganisateurNouvelleReservation(
            $evenement->organisateur_id,
            $user->username,
            $validated['nombre_tickets'],
            $evenement->titre
        );

        return response()->json([
            'message' => 'Reservation confirmed successfully!',
            'reservation' => $reservation->load('evenement'),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------

    /**
     * Return all reservations for a given event.
     * Accessible only by the event organizer.
     *
     * GET /api/reservations/evenement/{evenement}
     */
    public function chercherReservationParEvenement(Evenement $evenement): JsonResponse
    {
        // Only the manager (owner or accepted co-organizer) can view reservations for their event
        if (!$evenement->isManagedBy(Auth::id())) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $reservations = $evenement->reservations()
            ->with('user:id,username,email,telephone')
            ->latest()
            ->get();

        return response()->json([
            'event' => $evenement->titre,
            'reservations' => $reservations,
            'total' => $reservations->count(),
        ]);
    }

    /**
     * Return all reservations for a participant (user).
     * A user can only view their own reservations.
     *
     * GET /api/reservations/participant/{userId?}
     */
    public function chercherReservationParParticipant(Request $request, ?int $userId = null): JsonResponse
    {
        // Default to the authenticated user if no userId is provided
        $targetId = $userId ?? Auth::id();

        // A user can only view their own reservations
        if ($targetId !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $reservations = Reservation::where('user_id', $targetId)
            ->with(['evenement:id,titre,date_debut,date_fin,lieu,prix_spectateur', 'billets'])
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations,
            'total' => $reservations->count(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Cancellation
    // -------------------------------------------------------------------------

    /**
     * Cancel a reservation.
     *
     * Business rule: a reservation cannot be cancelled if the event
     * starts in less than 3 days.
     *
     * PATCH /api/reservations/{reservation}/annuler
     */
    public function annuler(Reservation $reservation): JsonResponse
    {
        // Only the reservation owner can cancel it
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // Check that the reservation is cancellable (not already cancelled)
        if (!$reservation->isCancellable()) {
            return response()->json([
                'message' => 'This reservation cannot be cancelled (current status: ' . $reservation->statut->value . ').',
            ], 422);
        }

        // Business rule: cancellation is forbidden less than 3 days before the event
        $evenement = $reservation->evenement;
        $daysRemaining = now()->diffInDays($evenement->date_debut, false); // false = negative if in the past

        if ($daysRemaining < 3) {
            return response()->json([
                'message' => "Cancellation is not allowed: the event starts in less than 3 days ({$evenement->date_debut->format('m/d/Y H:i')}).",
            ], 422);
        }

        $reservation->cancel();

        // Notify the event organizer about the cancellation
        $user = Auth::user();
        $evenement = $reservation->evenement;
        $this->notificationService->notifieOrganisateurReservationAnnulee(
            $evenement->organisateur_id,
            $user->username,
            $evenement->titre
        );

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'reservation' => $reservation->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Amount Calculation
    // -------------------------------------------------------------------------

    /**
     * Calculate the total amount of a reservation.
     * Amount = event ticket price × number of tickets.
     *
     * GET /api/reservations/{reservation}/montant
     */
    public function calculerMontant(Reservation $reservation): JsonResponse
    {
        // Only the reservation owner can view the amount
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $evenement = $reservation->evenement;

        $unitPrice = (float) $evenement->prix_spectateur;
        if ($evenement->is_tournoi && $reservation->ticket_type === 'participant') {
            $unitPrice = (float) $evenement->prix_participant;
        }

        $ticketCount = $reservation->nombre_tickets;
        $totalAmount = $unitPrice * $ticketCount;

        return response()->json([
            'reservation_id' => $reservation->id,
            'event' => $evenement->titre,
            'ticket_type' => $reservation->ticket_type ?? 'standard',
            'unit_price' => $unitPrice,
            'ticket_count' => $ticketCount,
            'total_amount' => round($totalAmount, 2),
            'currency' => 'TND',
        ]);
    }
}
