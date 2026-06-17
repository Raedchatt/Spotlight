<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$event = App\Models\Evenement::latest()->first();
if ($event) {
    $event->load('sponsors');
    print_r($event->toArray()['sponsors'] ?? 'No sponsors array');
} else {
    echo "No event found.";
}
