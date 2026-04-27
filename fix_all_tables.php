<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SHOW TABLES");
$fixed = [];

foreach ($tables as $t) {
    $tableName = $t->Tables_in_wajenzi;
    
    // Skip certain tables
    if (in_array($tableName, ['migrations', 'users', 'password_resets', 'personal_access_tokens', 'failed_jobs', 'jobs', 'notifications'])) {
        continue;
    }
    
    try {
        $cols = DB::select("SHOW COLUMNS FROM $tableName WHERE Field = 'id'");
        if (empty($cols)) continue;
        
        $idCol = $cols[0];
        if (strpos($idCol->Extra, 'auto_increment') === false) {
            // Missing AUTO_INCREMENT
            echo "$tableName - missing auto_increment (id type: $idCol->Type)\n";
            
            $maxId = DB::table($tableName)->max('id');
            if (!$maxId) $maxId = 0;
            $nextId = (int)$maxId + 1;
            
            try {
                DB::statement("ALTER TABLE $tableName MODIFY COLUMN id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT");
                DB::statement("ALTER TABLE $tableName AUTO_INCREMENT = $nextId");
                $fixed[] = $tableName;
                echo "  Fixed: $tableName (AUTO_INCREMENT = $nextId)\n";
            } catch (Exception $e) {
                echo "  Error: " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        // Skip problematic tables
    }
}

echo "\n=== Done! Fixed " . count($fixed) . " tables ===\n";