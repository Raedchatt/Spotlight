<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Revendeur;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Enums\Role;
use App\Enums\StatutReservation;
use Illuminate\Support\Facades\Cookie;

try {
    // 1. Setup a Reseller
    $resellerUser = User::create([
        'username' => 'reseller_id_test_'.time(),
        'email' => 'reseller_id_'.time().'@test.com',
        'password' => bcrypt('password'),
        'role' => Role::Revendeur,
    ]);
    $code = $resellerUser->revendeur->referral_code;
    $resellerId = $resellerUser->revendeur->id;
    echo "Reseller created: ID $resellerId, Code $code\n";

    // 2. Setup an Event
    $event = Evenement::first() ?? Evenement::create([
        'organisateur_id' => 1,
        'titre' => 'Test Event ID',
        'prix_spectateur' => 100,
        'capacite_spectateur' => 50,
        'statut' => \App\Enums\StatutEvenement::Ouvert,
        'date_debut' => now()->addDays(5),
        'date_fin' => now()->addDays(5)->addHours(2),
        'lieu' => 'Test Location',
    ]);
    echo "Using Event ID: {$event->id} (Price: {$event->prix_spectateur} TND)\n";

    // 3. Simulate Cookie storage
    // Note: In a CLI script, we can't easily use Cookie::queue and have it reflected in $request->cookie()
    // So we will simulate the lookup logic manually for verification.
    
    // 4. Create Reservation (Simulate Controller store)
    $participant = User::factory()->create(['role' => Role::Participant]);
    
    // Manual lookup as done in Controller
    $foundResellerId = Revendeur::where('referral_code', $code)->first()?->id;
    
    $reservation = Reservation::create([
        'user_id' => $participant->id,
        'evenement_id' => $event->id,
        'nombre_tickets' => 2,
        'statut' => StatutReservation::Pending,
        'revendeur_id' => $foundResellerId,
    ]);
    echo "Reservation created (#{$reservation->id}) with revendeur_id: {$reservation->revendeur_id}\n";

    // 5. Confirm Reservation (Triggers Observer commission)
    echo "Confirming reservation...\n";
    $reservation->update(['statut' => StatutReservation::Confirmed]);

    // 6. Verify Reseller Balance
    $resellerUser->revendeur->refresh();
    echo "New Reseller Balance: {$resellerUser->revendeur->balance} TND\n";
    
    $expectedCommission = ($event->prix_spectateur * 2) * 0.05; // 5% of total
    if (abs($resellerUser->revendeur->balance - $expectedCommission) < 0.001) {
        echo "SUCCESS: Commission of $expectedCommission TND credited correctly via ID!\n";
    } else {
        echo "FAILURE: Expected $expectedCommission TND, but got " . $resellerUser->revendeur->balance . "\n";
    }

    // Cleanup
    $reservation->delete();
    $participant->delete();
    $resellerUser->revendeur->delete();
    $resellerUser->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
