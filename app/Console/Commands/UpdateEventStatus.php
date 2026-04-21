<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    /**
     * The console command description.
     *
     * @var string
     */
    protected $signature = 'app:update-event-status';

    protected $description = 'Update event statuses (encours/ferme) based on their start and end dates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        // 1. Mark events as 'ferme' if they have ended
        $endedCount = \App\Models\Evenement::where('date_fin', '<', $now)
            ->whereNotIn('statut', [
                \App\Enums\StatutEvenement::Ferme,
                \App\Enums\StatutEvenement::Annule,
                \App\Enums\StatutEvenement::Rejete
            ])
            ->update(['statut' => \App\Enums\StatutEvenement::Ferme]);

        // 2. Mark events as 'encours' if they are currently happening
        // Only transition events that were approved (Ouvert or Valide)
        $startedCount = \App\Models\Evenement::where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->whereIn('statut', [
                \App\Enums\StatutEvenement::Ouvert,
                \App\Enums\StatutEvenement::Valide
            ])
            ->update(['statut' => \App\Enums\StatutEvenement::EnCours]);

        if ($endedCount > 0 || $startedCount > 0) {
            $this->info("Event states updated: {$endedCount} closed, {$startedCount} moved to in progress.");
        }
    }
}
