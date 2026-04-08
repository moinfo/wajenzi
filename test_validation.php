<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;

// Try creating a new site with name 'TEST-OK-123' (which is used by site ID 2)
echo "Testing unique validation...\n";
echo "Site ID 2 current name: " . Site::find(2)?->name . "\n";

$testSite = new Site([
    'name' => 'TEST-OK-123',  // Same name as site ID 2
    'location' => 'Test',
    'status' => 'ACTIVE',
    'created_by' => 1,
]);

// This should fail unique validation
echo "Testing validation...\n";
try {
    $testSite->validate(['name' => 'TEST-OK-123']);
    echo "Validation passed (unexpected)\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed (expected): " . json_encode($e->errors()) . "\n";
}