<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\EvenementController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Mock a user if needed, or just run as guest
// Auth::login(App\Models\User::first());

try {
    echo "Testing Notifications Index...\n";
    $notifCtrl = app(NotificationController::class);
    $resp = $notifCtrl->index();
    echo "Notifications Response Status: " . $resp->getStatusCode() . "\n";
    $json = $resp->getContent();
    if ($json === false) {
        echo "Notifications Serialization FAILED: " . json_last_error_msg() . "\n";
    } else {
        echo "Notifications Data: " . $json . "\n\n";
    }

    echo "Testing Events Search (Discovery)...\n";
    $eventCtrl = app(EvenementController::class);
    $req = new Request();
    $req->merge(['statut' => 'ouvert,valide,encours,en_attente', 'per_page' => 9]);
    $resp = $eventCtrl->search($req);
    echo "Events Search Response Status: " . $resp->getStatusCode() . "\n";
    $data = $resp->getData();
    echo "Events Count: " . (is_array($data) ? count($data) : (isset($data->total) ? $data->total : 'unknown')) . "\n";


}
catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
