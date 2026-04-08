<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Delete any sites with ID=0
DB::table('sites')->where('id', 0)->delete();

// Also check site_supervisor_assignments
DB::table('site_supervisor_assignments')->where('id', 0)->delete();

echo "=== Sites after cleanup ===\n";
$sites = DB::table('sites')->orderBy('id')->get();
foreach ($sites as $s) {
    echo "ID: {$s->id}, Name: {$s->name}\n";
}

echo "\n=== Assignments ===\n";
$assignments = DB::table('site_supervisor_assignments')->get();
foreach ($assignments as $a) {
    echo "ID: {$a->id}, Site ID: {$a->site_id}\n";
}