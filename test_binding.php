<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;

// Test route model binding - simulate what happens
$site = Site::find(2);
echo "Site::find(2): ";
if ($site) {
    echo "ID: {$site->id}, Name: {$site->name}\n";
} else {
    echo "null\n";
}

// Try with route binding directly
$site2 = Site::where('id', 2)->first();
echo "Site::where('id', 2)->first(): ";
if ($site2) {
    echo "ID: {$site2->id}, Name: {$site2->name}\n";
} else {
    echo "null\n";
}

// Check validation
$siteName = 'TEST-OK-123';
$siteId = 0; // what route model binding might be passing
echo "\nValidation check: unique:sites,name,$siteId";
echo " -> Would find name: ";
$existing = Site::where('name', $siteName)
    ->when($siteId, fn($q) => $q->where('id', '!=', $siteId))
    ->count();
echo "$existing\n";