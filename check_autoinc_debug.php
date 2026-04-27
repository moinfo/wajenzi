<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking project_site_visits ===\n";

$table = DB::select("SHOW CREATE TABLE project_site_visits");
echo $table[0]->{'Create Table'} . "\n\n";

$maxId = DB::table('project_site_visits')->max('id');
echo "Max ID: $maxId\n";

$recent = DB::table('project_site_visits')->orderBy('id', 'desc')->limit(5)->get();
foreach ($recent as $r) {
    echo "ID: $r->id, project_id: $r->project_id\n";
}