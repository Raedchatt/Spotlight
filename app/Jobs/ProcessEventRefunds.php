<?php

namespace App\Jobs;

use App\Models\Evenement;
use App\Models\Reservation;
use App\Enums\StatutReservation;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessEventRefunds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $evenementId;
    public $timeout = 0; // Prevent timeout for large events if queue driver supports it

    /**
     * Create a new job instance.
     */
    public function __construct(int $evenementId)
    {
        $this->evenementId = $evenementId;
    }

    /**
     * Execute the job.
     */
    public function handle(RefundService $refundService): void
    {
        $evenement = Evenement::with('reservations.paiements')->find($this->evenementId);

        if (!$evenement) {
            return;
        }

        $reservations = $evenement->reservations()
            ->whereIn('statut', [StatutReservation::Confirmed, StatutReservation::Pending])
            ->get();

        $refundedCount = 0;
        $failedCount = 0;

        foreach ($reservations as $reservation) {
            try {
                // cancelReservation is false because we might want to batch-update
                // or let the RefundService do it internally
                $refundService->refundReservation($reservation, true);
                $refundedCount++;
            } catch (Exception $e) {
                // Keep moving to refund the remaining reservations
                Log::error("Mass refund error for Reservation #{$reservation->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        Log::info("Mass Refunds for Event #{$evenement->id} Completed. Success: {$refundedCount}, Failed: {$failedCount}");
    }
}
