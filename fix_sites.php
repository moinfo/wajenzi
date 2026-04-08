<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Delete all ID=0
DB::table('sites')->where('id', 0)->delete();

$sites = DB::table('sites')->orderBy('id')->get();
echo "Sites after cleanup:\n";
foreach ($sites as $s) {
    echo "  ID: {$s->id}, Name: {$s->name}\n";
}