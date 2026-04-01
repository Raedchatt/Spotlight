<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Enums\StatutReservation;
use App\Enums\StatutPaiement;
use App\Jobs\ProcessEventRefunds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Refund;
use Exception;

class RefundService
{
    protected FinancialRecordService $financialRecordService;

    public function __construct(FinancialRecordService $financialRecordService)
    {
        $this->financialRecordService = $financialRecordService;
    }

    /**
     * Refunds a single reservation via Stripe and logs it.
     */
    public function refundReservation(Reservation $reservation, bool $cancelReservation = true): bool
    {
        $evenement = $reservation->evenement;

        // Block if event is already paid out
        if ($evenement->is_paid_out) {
            throw new Exception("Cannot refund reservation. The event has already been paid out to the organizer.");
        }

        // Only process confirmed reservations (which should have successful payments)
        if ($reservation->statut !== StatutReservation::Confirmed) {
            // If it's just pending, we can just cancel it without a Stripe refund.
            if ($cancelReservation) {
                $reservation->update(['statut' => StatutReservation::Cancelled]);
            }
            return true;
        }

        $paiement = Paiement::where('reservation_id', $reservation->id)
            ->where('statut', StatutPaiement::Succeeded)
            ->first();

        DB::beginTransaction();
        try {
            if ($paiement && $paiement->stripe_payment_intent_id) {
                Stripe::setApiKey(config('services.stripe.secret'));

                // 1. Call Stripe Refund API
                $refund = Refund::create([
                    'payment_intent' => $paiement->stripe_payment_intent_id,
                ]);

                // 2. Update status
                $paiement->update(['statut' => StatutPaiement::Refunded]);

                // 3. Create financial record
                // Amount refunded is the full payment amount. Stripe refund object amount is in cents
                $this->financialRecordService->createRefundRecord(
                    $paiement, 
                    $refund->id, 
                    $refund->amount
                );
            }

            if ($cancelReservation) {
                $reservation->update(['statut' => StatutReservation::Cancelled]);
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Refund failed for reservation {$reservation->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Wrapper to trigger the bulk refund process for an entire event.
     */
    public function refundEvent(Evenement $evenement): void
    {
        // Block if event is already paid out
        if ($evenement->is_paid_out) {
            throw new Exception("Cannot process mass refund. The event has already been paid out to the organizer.");
        }

        // Dispatch background job to handle the refunds
        ProcessEventRefunds::dispatch($evenement->id);
    }
}
