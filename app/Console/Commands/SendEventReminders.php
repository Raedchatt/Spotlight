<?php

namespace App\Console\Commands;

use App\Models\Evenement;
use App\Enums\StatutEvenement;
use App\Enums\StatutReservation;
use App\Mail\EventReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 24-hour reminder emails to participants of upcoming events.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $targetTime = $now->copy()->addHours(24);

        // Find events that are 'Ouvert', starting within the next 24 hours, and haven't sent a reminder yet.
        $events = Evenement::where('statut', StatutEvenement::Ouvert->value)
            ->where('date_debut', '<=', $targetTime)
            ->where('date_debut', '>', $now)
            ->where('is_reminder_sent', false)
            ->with([
                'reservations' => function ($query) {
                    $query->where('statut', StatutReservation::Confirmed->value)->with('user');
                }
            ])
            ->get();

        $this->info("Found {$events->count()} event(s) to send reminders for.");

        foreach ($events as $event) {
            $reservationsCount = 0;
            foreach ($event->reservations as $reservation) {
                if ($reservation->user && $reservation->user->email) {
                    Mail::to($reservation->user->email)->send(new EventReminderMail($event, $reservation));
                    $reservationsCount++;
                }
            }

            if ($reservationsCount > 0) {
                // Mark reminder as sent so we don't spam them again.
                $event->update(['is_reminder_sent' => true]);
                $this->info("Sent {$reservationsCount} reminder(s) for event ID {$event->id} ({$event->titre}).");
            } else {
                $this->info("No confirmed reservations found for event ID {$event->id}, skipping update.");
            }
        }

        $this->info('Event reminders process completed.');
    }
}
