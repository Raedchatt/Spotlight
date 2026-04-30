<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Evenement;
use App\Enums\StatutEvenement;

class SyncEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize event statuses based on their start and end dates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $updatedCount = 0;

        // 1. Transition 'ouvert' to 'encours' if now >= date_debut and now < date_fin
        $startedEvents = Evenement::where('statut', StatutEvenement::Ouvert->value)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>', $now)
            ->get();

        foreach ($startedEvents as $event) {
            $event->update(['statut' => StatutEvenement::EnCours]);
            $updatedCount++;
            $this->info("Event ID {$event->id} status updated to EnCours.");
        }

        // 2. Transition 'ouvert' or 'encours' to 'ferme' if now >= date_fin
        $finishedEvents = Evenement::whereIn('statut', [StatutEvenement::Ouvert->value, StatutEvenement::EnCours->value])
            ->where('date_fin', '<=', $now)
            ->get();

        foreach ($finishedEvents as $event) {
            $event->update(['statut' => StatutEvenement::Ferme]);
            $updatedCount++;
            $this->info("Event ID {$event->id} status updated to Ferme.");
        }

        $this->info("Successfully synchronized $updatedCount event(s).");
    }
}
