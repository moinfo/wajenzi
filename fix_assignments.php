<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix assignment ID=0
$assignments = DB::table('site_supervisor_assignments')->where('id', 0)->get();
echo "Assignments with ID=0: " . $assignments->count() . "\n";

$maxId = DB::table('site_supervisor_assignments')->max('id');
echo "Max ID: $maxId\n";

foreach ($assignments as $a) {
    $newId = $maxId + 1;
    DB::table('site_supervisor_assignments')->where('id', 0)->update(['id' => $newId]);
    echo "Updated ID 0 to $newId\n";
    $maxId = $newId;
}

echo "\n=== After fix ===\n";
$assignments = App\Models\SiteSupervisorAssignment::with(['site', 'supervisor'])->get();
foreach ($assignments as $a) {
    echo "ID: {$a->id}, is_active: {$a->is_active}, Supervisor: {$a->supervisor?->name}\n";
}