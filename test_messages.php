<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testUserMessages($userId) {
    echo "\n--- Testing User $userId ---\n";
    $user = App\Models\User::find($userId);
    Auth::login($user);
    $controller = app()->make(App\Http\Controllers\MessageController::class);
    $response = $controller->recent();
    $data = $response->getData(true);
    
    if (empty($data['conversations'])) {
        echo "No conversations found.\n";
        return;
    }

    foreach ($data['conversations'] as $conv) {
        echo "Other user ID: " . $conv['id'] . "\n";
        echo "Displayed Name: " . $conv['name'] . "\n";
    }
}

testUserMessages(1); // Organizer
testUserMessages(2); // Participant
