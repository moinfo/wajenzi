<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$site = App\Models\Site::find(0);
echo "Before: " . $site->name . "\n";
$site->update(['name' => 'TEST-OK-123']);
echo "After: " . App\Models\Site::find(0)->name . "\n";