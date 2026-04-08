<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;

// Create a test site - should get ID 3 now
$site = Site::create([
    'name' => 'Test-Auto-Inc-' . time(),
    'location' => 'Test Location',
    'status' => 'ACTIVE',
    'created_by' => 1,
]);

echo "Created site ID: {$site->id}, Name: {$site->name}\n";

// Check all sites
echo "\nAll sites:\n";
foreach (Site::all() as $s) {
    echo "  ID: {$s->id}, Name: {$s->name}\n";
}