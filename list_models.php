<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.gemini.key');
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$response = Illuminate\Support\Facades\Http::get($url);
if ($response->successful()) {
    echo "Available Models:\n";
    foreach ($response->json()['models'] as $model) {
        echo "- " . $model['name'] . " (" . implode(", ", $model['supportedGenerationMethods']) . ")\n";
    }
} else {
    echo "Failed to list models: " . $response->status() . "\n";
    print_r($response->json());
}
