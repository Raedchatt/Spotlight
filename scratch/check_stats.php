<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\EvenementController;
use Illuminate\Http\Request;

$controller = app(EvenementController::class);
$request = Request::create('/api/events/37', 'GET');
$request->headers->set('Accept', 'application/json');
$response = $controller->show($request, 37);
$data = $response->getData();

echo "Event: " . $data->titre . "\n";
echo "Is Equipe: " . ($data->stats->is_equipe ? 'Yes' : 'No') . "\n";
echo "Participant Capacity Raw: " . $data->stats->participant_capacity_raw . "\n";
echo "Participant Remaining: " . $data->stats->participant_remaining . "\n";
echo "Spectator Remaining: " . $data->stats->spectator_remaining . "\n";
