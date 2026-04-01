<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Paiement;
use App\Enums\StatutPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Transfer;
use Exception;

class PayoutService
{
    protected FinancialRecordService $financialRecordService;

    public function __construct(FinancialRecordService $financialRecordService)
    {
        $this->financialRecordService = $financialRecordService;
    }

    /**
     * Process automated payout for a closed event.
     */
    public function processEventPayout(Evenement $evenement): void
    {
        if ($evenement->is_paid_out) {
            Log::info("Event #{$evenement->id} is already paid out. Skipping.");
            return;
        }

        if (!$evenement->organisateur || !$evenement->organisateur->organisateur || !$evenement->organisateur->organisateur->stripe_account_id) {
            Log::warning("Event #{$evenement->id} has no valid connected Stripe account for the organizer. Payout skipped.");
            return;
        }

        DB::beginTransaction();
        try {
            // Lock event row if possible
            $lockedEvent = Evenement::where('id', $evenement->id)->lockForUpdate()->first();
            
            if ($lockedEvent->is_paid_out) {
                DB::rollBack();
                return; // Prevent duplicate payout
            }

            // 1. Calculate total revenue using precise integers (cents)
            $reservationsIds = $lockedEvent->reservations()->pluck('id');
            
            if ($reservationsIds->isEmpty()) {
                DB::rollBack();
                return;
            }

            $paiements = Paiement::whereIn('reservation_id', $reservationsIds)->get();
            
            $totalCents = 0;
            foreach ($paiements as $paiement) {
                if ($paiement->statut === StatutPaiement::Succeeded) {
                    $totalCents += (int) round($paiement->montant * 100);
                } 
                // Refunded amounts generally don't get paid out. Wait, does a refunded payment have status 'Refunded'? 
                // Yes, statutory Succeeded is the only one we pay out.
            }

            if ($totalCents <= 0) {
                // Nothing to pay out
                Log::info("Event #{$lockedEvent->id} has total valid revenue <= 0. Skipping empty payout.");
                DB::rollBack();
                return;
            }

            // 2. Calculate Commission (10%) safely in cents
            $commissionCents = (int) floor(($totalCents * 10) / 100);
            $organizerAmountCents = $totalCents - $commissionCents;

            if ($organizerAmountCents <= 0) {
                DB::rollBack();
                return;
            }

            // 3. Send Stripe Transfer
            Stripe::setApiKey(config('services.stripe.secret'));

            $organizerStripeId = $lockedEvent->organisateur->organisateur->stripe_account_id;

            try {
                $transfer = Transfer::create([
                    'amount' => $organizerAmountCents,
                    'currency' => 'eur',
                    'destination' => $organizerStripeId,
                    'description' => "Payout for Event: " . $lockedEvent->titre,
                ]);

                // 4. Update Event and record
                $lockedEvent->update([
                    'is_paid_out' => true,
                    'paid_out_at' => now(),
                ]);

                $this->financialRecordService->createPayoutRecord(
                    $lockedEvent, 
                    $transfer->id, 
                    $organizerAmountCents, 
                    'completed'
                );
                
                Log::info("Successfully paid out event #{$lockedEvent->id}. Amount: {$organizerAmountCents} cents.");

            } catch (Exception $stripeException) {
                // If transfer fails, record failure and DO NOT mark as paid_out
                $this->financialRecordService->createPayoutRecord(
                    $lockedEvent, 
                    'error_' . uniqid(), 
                    $organizerAmountCents, 
                    'failed',
                    substr($stripeException->getMessage(), 0, 255) // safe description length
                );
                Log::error("Stripe Transfer failed for Event #{$lockedEvent->id}: " . $stripeException->getMessage());
                // Rethrow will rollback
                throw $stripeException;
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Payout process failed for event #{$evenement->id}: " . $e->getMessage());
        }
    }
}
