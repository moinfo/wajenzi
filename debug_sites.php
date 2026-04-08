<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check table structure
echo "=== TABLE STRUCTURE ===\n";
$columns = DB::select("DESCRIBE sites");
foreach ($columns as $col) {
    echo "{$col->Field}: {$col->Type} - Key: {$col->Key}\n";
}

// Check max id in table
echo "\n=== MAX ID ===\n";
$maxId = DB::table('sites')->max('id');
echo "Max ID: $maxId\n";

// Try to get next auto_increment value
echo "\n=== AUTO_INCREMENT ===\n";
try {
    $result = DB::select("SHOW TABLE STATUS LIKE 'sites'");
    if (isset($result[0])) {
        echo "Auto_increment: " . $result[0]->Auto_increment . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check if there's a trigger
echo "\n=== TRIGGERS ===\n";
$triggers = DB::select("SHOW TRIGGERS LIKE 'sites'");
echo "Found " . count($triggers) . " triggers\n";