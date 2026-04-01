<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\FinancialRecord;
use App\Models\Paiement;

class FinancialRecordService
{
    /**
     * Create a financial record for a successful payment.
     */
    public function createPaymentRecord(Paiement $paiement, string $sessionId): FinancialRecord
    {
        return FinancialRecord::create([
            'paiement_id' => $paiement->id,
            'evenement_id' => $paiement->reservation->evenement_id,
            'type' => 'payment',
            'amount' => (int) round($paiement->montant * 100), // convert to cents
            'currency' => $paiement->currency ?? 'eur',
            'status' => 'completed',
            'stripe_reference' => $sessionId,
            'description' => 'Payment for reservation #' . $paiement->reservation_id,
        ]);
    }

    /**
     * Create a financial record for a successful refund.
     */
    public function createRefundRecord(Paiement $paiement, string $refundId, int $amountCents): FinancialRecord
    {
        return FinancialRecord::create([
            'paiement_id' => $paiement->id,
            'evenement_id' => $paiement->reservation->evenement_id,
            'type' => 'refund',
            'amount' => $amountCents, // Amount is expected in cents
            'currency' => $paiement->currency ?? 'eur',
            'status' => 'completed',
            'stripe_reference' => $refundId,
            'description' => 'Refund for reservation #' . $paiement->reservation_id,
        ]);
    }

    /**
     * Create a financial record for a payout.
     */
    public function createPayoutRecord(Evenement $evenement, string $reference, int $amountCents, string $status = 'completed', ?string $description = null): FinancialRecord
    {
        return FinancialRecord::create([
            'paiement_id' => null,
            'evenement_id' => $evenement->id,
            'type' => 'payout',
            'amount' => $amountCents, // Amount is expected in cents
            'currency' => 'eur',
            'status' => $status,
            'stripe_reference' => $reference,
            'description' => $description ?? 'Payout for event #' . $evenement->id,
        ]);
    }
}
