<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check current max ID
$maxId = DB::table('sites')->max('id');
echo "Max ID in sites: $maxId\n";

// Set AUTO_INCREMENT to max + 1 (so next insert gets next available ID)
$nextId = $maxId + 1;
DB::statement("ALTER TABLE sites AUTO_INCREMENT = $nextId");
echo "Set AUTO_INCREMENT to $nextId\n";

// Also check if there's a primary key issue
$columns = DB::select("SHOW COLUMNS FROM sites WHERE Key = 'PRI'");
echo "Primary key column: " . ($columns[0]->Field ?? 'none') . "\n";