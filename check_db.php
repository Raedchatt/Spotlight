<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$n = App\Models\Notification::where('type', 'invitation_collaboration')->latest()->first();
file_put_contents('last_notif.txt', json_encode($n, JSON_PRETTY_PRINT));
