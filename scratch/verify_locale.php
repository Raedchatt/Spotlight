<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use Illuminate\Support\Facades\App;

$cat = Category::latest()->first();

if (!$cat) {
    echo "No category found.\n";
    exit;
}

App::setLocale('ar');
echo "Arabic: " . $cat->toArray()['label'] . "\n";

App::setLocale('fr');
echo "French: " . $cat->toArray()['label'] . "\n";

App::setLocale('en');
echo "English: " . $cat->toArray()['label'] . "\n";
