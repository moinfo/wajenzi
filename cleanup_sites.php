<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Current Sites ===\n";
$sites = DB::table('sites')->orderBy('id')->get();
foreach ($sites as $s) {
    echo "ID: {$s->id}, Name: {$s->name}\n";
}

// Delete any site with ID=0
DB::table('sites')->where('id', 0)->delete();

echo "\n=== After cleanup ===\n";
$sites = DB::table('sites')->orderBy('id')->get();
foreach ($sites as $s) {
    echo "ID: {$s->id}, Name: {$s->name}\n";
}