<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// MySQL query to check AUTO_INCREMENT
$result = DB::select("SHOW TABLE STATUS LIKE 'sites'");
echo "Sites table AUTO_INCREMENT: " . $result[0]->Auto_increment . "\n";