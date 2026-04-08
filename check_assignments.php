<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$assignments = App\Models\SiteSupervisorAssignment::all();
foreach ($assignments as $a) {
    echo "ID: {$a->id}, is_active: {$a->is_active}, user_id: {$a->user_id}\n";
}