<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$events = App\Models\Evenement::all();
foreach($events as $e) {
    echo "ID: {$e->id} | Date: {$e->date_debut} | Statut: " . ($e->statut instanceof \App\Enums\StatutEvenement ? $e->statut->value : $e->statut) . " | Reminder: " . ($e->is_reminder_sent ? 'Yes' : 'No') . "\n";
}
