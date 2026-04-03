<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Commission;
use App\Enums\StatutCommission;
use App\Enums\StatutReservation;

class CommissionService
{
    /**
     * Calculate the revenue split for a reservation.
     * 
     * Rule 1 (Non-affiliate): Organizer 80%, Admin 20%
     * Rule 2 (Affiliate): Organizer 80%, Admin 15%, Reseller 5%
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function calculate(Reservation $reservation): array
    {
        $totalPrice = $reservation->getTotalPrice();
        $isAffiliate = !empty($reservation->revendeur_id);

        $organizerShare = $totalPrice * 0.80;
        
        if ($isAffiliate) {
            $resellerShare = $totalPrice * 0.05;
            $adminShare = $totalPrice * 0.15;
        } else {
            $resellerShare = 0;
            $adminShare = $totalPrice * 0.20;
        }

        return [
            'total' => $totalPrice,
            'organizer_share' => $organizerShare,
            'admin_share' => $adminShare,
            'reseller_share' => $resellerShare,
            'is_affiliate' => $isAffiliate,
        ];
    }

    /**
     * Calculate and store the commission for a reservation.
     * 
     * @param Reservation $reservation
     * @return Commission
     */
    public function createForReservation(Reservation $reservation): Commission
    {
        $shares = $this->calculate($reservation);

        // All commissions stay Pending and must be approved manually by admin
        $status = StatutCommission::Pending;

        return Commission::create([
            'evenement_id' => $reservation->evenement_id,
            'reservation_id' => $reservation->id,
            'revendeur_id' => $reservation->revendeur_id,
            'commission_organisateur' => $shares['organizer_share'],
            'commission_admin' => $shares['admin_share'],
            'commission_revendeur' => $shares['reseller_share'],
            'status' => $status,
        ]);
    }
}
