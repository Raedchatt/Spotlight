<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Test email from Spotlight', function ($msg) {
        $msg->to('raedc@example.com')->subject('Test SMTP Connection');
    });
    echo "Email sent successfully.\n";
} catch (\Exception $e) {
    echo "Error sending email:\n" . $e->getMessage() . "\n";
}
