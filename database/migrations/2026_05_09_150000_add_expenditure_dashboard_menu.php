<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Locate the Finance parent menu by route — id can differ between
        // environments — and skip if a row with the same name already exists.
        $financeId = DB::table('menus')->where('route', 'finance')->value('id');
        if (! $financeId) {
            return;
        }

        $exists = DB::table('menus')
            ->where('parent_id', $financeId)
            ->where('name', 'Expenditure Dashboard')
            ->exists();
        if ($exists) {
            return;
        }

        DB::table('menus')->insert([
            'name'       => 'Expenditure Dashboard',
            'route'      => 'finance.expenditure_dashboard',
            'icon'       => 'fa fa-chart-line',
            'parent_id'  => $financeId,
            'list_order' => 4,
            'status'     => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')
            ->where('route', 'finance.expenditure_dashboard')
            ->where('name', 'Expenditure Dashboard')
            ->delete();
    }
};
