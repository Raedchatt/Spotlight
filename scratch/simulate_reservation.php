<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Reservation;
use App\Models\User;
use App\Enums\StatutReservation;

$user = User::where('role', 'participant')->first();
if (!$user) {
    echo "No participant found\n";
    exit;
}

// Simulate a team reservation (8 tickets)
$reservation = Reservation::create([
    'user_id' => $user->id,
    'evenement_id' => 37,
    'ticket_type' => 'participant',
    'nombre_tickets' => 8,
    'statut' => StatutReservation::Confirmed
]);

echo "Reservation created ID: " . $reservation->id . "\n";
