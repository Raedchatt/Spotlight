<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\AIReformulationService();
$testMessage = "Bonjour, je voudrais reserver une place pour demain, est-ce possible?";
echo "Testing with: $testMessage\n";
$result = $service->reformuler($testMessage);
echo "Result: $result\n";
if ($result !== $testMessage) {
    echo "SUCCESS: Reformulation worked!\n";
} else {
    echo "FAILURE: Returned original message (likely quota error or no change needed).\n";
}
