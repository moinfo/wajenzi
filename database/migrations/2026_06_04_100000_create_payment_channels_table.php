<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable list of payment channels (banks / mobile money / cash) used by
     * the Site Paylog feature. Admin-editable so finance can add new banks later.
     */
    public function up(): void
    {
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->nullable(); // bank | mobile | cash
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the defaults from the spec (idempotent on name).
        $now = now();
        $defaults = [
            ['name' => 'CRDB',      'type' => 'bank',   'sort_order' => 1],
            ['name' => 'NMB',       'type' => 'bank',   'sort_order' => 2],
            ['name' => 'M-Pesa',    'type' => 'mobile', 'sort_order' => 3],
            ['name' => 'TigoPesa',  'type' => 'mobile', 'sort_order' => 4],
            ['name' => 'Airtel',    'type' => 'mobile', 'sort_order' => 5],
            ['name' => 'Cash',      'type' => 'cash',   'sort_order' => 6],
        ];

        foreach ($defaults as $row) {
            DB::table('payment_channels')->insertOrIgnore(array_merge($row, [
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_channels');
    }
};
