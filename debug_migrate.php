<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
try {
    $kernel->call('migrate', ['--force' => true]);
    file_put_contents('migration_error.txt', $kernel->output());
} catch (\Exception $e) {
    file_put_contents('migration_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
}
echo "Done.";
