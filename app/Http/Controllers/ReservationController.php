<?php

namespace App\Http\Controllers;

use App\Enums\StatutReservation;
use App\Models\Evenement;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
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
        // Only the organizer can view reservations for their event
        if ($evenement->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $reservations = $evenement->reservations()
            ->with('user:id,username,email,telephone')
            ->latest()
            ->get();

        return response()->json([
            'event'        => $evenement->titre,
            'reservations' => $reservations,
            'total'        => $reservations->count(),
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
            ->with('evenement:id,titre,date_debut,date_fin,lieu,prix_spectateur')
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations,
            'total'        => $reservations->count(),
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
        $evenement     = $reservation->evenement;
        $daysRemaining = now()->diffInDays($evenement->date_debut, false); // false = negative if in the past

        if ($daysRemaining < 3) {
            return response()->json([
                'message' => "Cancellation is not allowed: the event starts in less than 3 days ({$evenement->date_debut->format('m/d/Y H:i')}).",
            ], 422);
        }

        $reservation->cancel();

        return response()->json([
            'message'     => 'Reservation cancelled successfully.',
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

        $evenement     = $reservation->evenement;
        $unitPrice     = (float) $evenement->prix_spectateur;
        $ticketCount   = $reservation->nombre_tickets;
        $totalAmount   = $unitPrice * $ticketCount;

        return response()->json([
            'reservation_id' => $reservation->id,
            'event'          => $evenement->titre,
            'unit_price'     => $unitPrice,
            'ticket_count'   => $ticketCount,
            'total_amount'   => round($totalAmount, 2),
            'currency'       => 'TND',
        ]);
    }
}
