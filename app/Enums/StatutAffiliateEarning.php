<?php

namespace App\Enums;

/**
 * Represents the lifecycle of an affiliate earning.
 *
 * - Pending:   The commission is recorded but not yet finalized.
 * - Approved:  The commission is confirmed and ready for payout.
 * - Reversed:  The commission was revoked (e.g. due to cancellation).
 */
enum StatutAffiliateEarning: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Reversed = 'reversed';
}
