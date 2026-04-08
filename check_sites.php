<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sites = App\Models\Site::with('currentSupervisor')->get();

echo "=== All Sites ===\n";
foreach ($sites as $s) {
    echo "ID: {$s->id}, Name: {$s->name}, Supervisor: " . ($s->currentSupervisor?->name ?? 'none') . "\n";
}

echo "\n=== Sites in API format ===\n";
$api = new App\Http\Controllers\Api\V1\SiteApiController();
foreach ($sites as $s) {
    $formatted = $api->formatSite($s);
    echo "ID: {$formatted['id']}, Name: {$formatted['name']}, Buttons should show\n";
}