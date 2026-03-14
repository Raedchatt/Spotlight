<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('role', 'participant')->first();
if (!$user) {
    echo "No participant user found.\n";
    exit;
}

echo "Current interests: " . json_encode($user->interests) . "\n";

$testInterests = ['music', 'tech', 'art'];
$user->update(['interests' => $testInterests]);

$user->refresh();
echo "Updated interests: " . json_encode($user->interests) . "\n";

if ($user->interests === $testInterests) {
    echo "SUCCESS: Interests saved correctly.\n";
} else {
    echo "FAILURE: Interests did not save correctly.\n";
}
