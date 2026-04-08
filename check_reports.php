<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SiteDailyReport;

$reports = SiteDailyReport::with(['site', 'supervisor'])->get();

echo "=== All Reports ===\n";
foreach ($reports as $r) {
    $site = $r->site ? $r->site->name : 'NULL';
    $supervisor = $r->supervisor ? $r->supervisor->name : 'NULL (PROBLEM!)';
    echo "ID: {$r->id}, Site: {$site}, Supervisor: {$supervisor}\n";
}