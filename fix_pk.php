<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// First drop any existing indexes on id (use separate queries)
try {
    DB::statement("ALTER TABLE sites DROP INDEX id");
    echo "Dropped index 'id'\n";
} catch (\Exception $e) {
    echo "No index to drop or other error: " . $e->getMessage() . "\n";
}

// Now add primary key with auto_increment
try {
    DB::statement("ALTER TABLE sites MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
    echo "Set PRIMARY KEY with AUTO_INCREMENT on id\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Set proper auto increment
try {
    DB::statement("ALTER TABLE sites AUTO_INCREMENT = 3");
    echo "Set AUTO_INCREMENT to 3\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Verify
$columns = DB::select("DESCRIBE sites");
foreach ($columns as $col) {
    if ($col->Field === 'id') {
        echo "\nID column now:\n";
        echo "  Type: {$col->Type}\n";
        echo "  Key: {$col->Key}\n";
        echo "  Extra: {$col->Extra}\n";
    }
}