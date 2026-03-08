<?php

namespace App\Enums;

/**
 * Represents the lifecycle of a reservation.
 *
 * - Pending:   The user has requested a spot but it is not yet confirmed.
 * - Confirmed: The organizer (or system) has confirmed the reservation.
 * - Cancelled: The reservation was cancelled by the user or organizer.
 */
enum StatutReservation: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
