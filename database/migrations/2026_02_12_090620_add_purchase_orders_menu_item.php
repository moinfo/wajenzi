<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert Purchase Orders menu under Procurement (parent_id = 97)
        // Between Quotation Comparisons (list_order 4) and Material Inspections (list_order 5)
        DB::table('menus')->where('parent_id', 97)->where('list_order', '>=', 5)->increment('list_order');

        DB::table('menus')->insert([
            'name' => 'Purchase Orders',
            'route' => 'purchase_orders',
            'icon' => 'fa fa-shopping-cart',
            'parent_id' => 97,
            'list_order' => 5,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'purchase_orders')->where('parent_id', 97)->delete();
        DB::table('menus')->where('parent_id', 97)->where('list_order', '>', 5)->decrement('list_order');
    }
};
