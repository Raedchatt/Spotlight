<?php

namespace App\Console\Commands;

use App\Enums\StatutEvenement;
use App\Models\Evenement;
use App\Services\PayoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPayouts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payouts:process';

    /**
     * The console command description.
     */
    protected $description = 'Process automated payouts for events that closed more than 1 day ago';

    /**
     * Execute the console command.
     */
    public function handle(PayoutService $payoutService): int
    {
        $this->info('Starting payout processing...');

        $eligibleEvents = Evenement::where('statut', StatutEvenement::Ferme)
            ->where('is_paid_out', false)
            ->where('date_fin', '<=', Carbon::now()->subDay())
            ->get();

        if ($eligibleEvents->isEmpty()) {
            $this->info('No eligible events found for payout.');
            return Command::SUCCESS;
        }

        $this->info("Found {$eligibleEvents->count()} eligible event(s).");

        $successCount = 0;
        $failCount = 0;

        /** @var \App\Models\Evenement $event */
        foreach ($eligibleEvents as $event) {
            $this->info("Processing payout for Event #{$event->id}: {$event->titre}");

            try {
                $payoutService->processEventPayout($event);

                // Re-check if it was paid out (it could have been skipped for valid reasons)
                $event->refresh();
                if ($event->is_paid_out) {
                    $successCount++;
                    $this->info("  ✓ Payout completed for Event #{$event->id}");
                } else {
                    $this->warn("  ⚠ Payout skipped for Event #{$event->id} (no revenue or missing Stripe account)");
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->error("  ✗ Payout failed for Event #{$event->id}: {$e->getMessage()}");
                Log::error("Scheduled payout failed for Event #{$event->id}: " . $e->getMessage());
            }
        }

        $this->info("Payout processing complete. Success: {$successCount}, Failed: {$failCount}");

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
