<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing project_site_visits ===\n";

$bad = DB::table('project_site_visits')->where('id', 0)->first();
if ($bad) {
    echo "Deleting record with id=0...\n";
    DB::table('project_site_visits')->where('id', 0)->delete();
}

$maxId = DB::table('project_site_visits')->max('id');
if (!$maxId) $maxId = 0;
$nextId = (int)$maxId + 1;

DB::statement("ALTER TABLE project_site_visits MODIFY COLUMN id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT");
DB::statement("ALTER TABLE project_site_visits AUTO_INCREMENT = $nextId");
echo "Fixed! AUTO_INCREMENT = $nextId\n";