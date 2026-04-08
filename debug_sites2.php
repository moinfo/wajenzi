<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;

// Direct SQL to check
$db = app(Connection::class);

// Show indexes on the table
echo "=== INDEXES ===\n";
$indexes = $db->select("SHOW INDEX FROM sites");
foreach ($indexes as $idx) {
    echo "Key: {$idx->Key_name}, Column: {$idx->Column_name}, Seq: {$idx->Seq_in_index}\n";
}

// Check if there's any unique constraint on id
echo "\n=== UNIQUE CHECK ===\n";
$duplicates = $db->select("SELECT id, COUNT(*) as cnt FROM sites GROUP BY id HAVING cnt > 1");
echo "Duplicates found: " . count($duplicates) . "\n";
foreach ($duplicates as $d) {
    echo "  ID: {$d->id}, Count: {$d->cnt}\n";
}