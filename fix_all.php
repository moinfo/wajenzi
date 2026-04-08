<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix all tables with ID=0
$tables = ['sites', 'site_supervisor_assignments', 'site_daily_reports'];

foreach ($tables as $table) {
    $count = DB::table($table)->where('id', 0)->count();
    if ($count > 0) {
        echo "Fixing $table: $count records with ID=0\n";
        $maxId = DB::table($table)->max('id');
        
        DB::table($table)->where('id', 0)->update(['id' => $maxId + 1]);
        echo "Updated to IDs: " . ($maxId + 1) . " - " . ($maxId + $count) . "\n";
    }
}

echo "\n=== All tables after fix ===\n";
echo "Sites: " . DB::table('sites')->count() . "\n";
echo "Assignments: " . DB::table('site_supervisor_assignments')->count() . "\n";
echo "Reports: " . DB::table('site_daily_reports')->count() . "\n";