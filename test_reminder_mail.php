<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Evenement;
use App\Models\Reservation;
use App\Mail\EventReminderMail;
use Illuminate\Support\Facades\Mail;

try {
    $event = Evenement::find(35);
    $reservation = Reservation::find(22);
    
    if (!$event || !$reservation) {
        throw new \Exception("Event or Reservation not found.");
    }

    Mail::to('raedchattaoui119@gmail.com')->send(new EventReminderMail($event, $reservation));
    echo "EventReminderMail sent successfully to raedchattaoui119@gmail.com\n";
} catch (\Exception $e) {
    echo "Error sending EventReminderMail:\n" . $e->getMessage() . "\n";
}
