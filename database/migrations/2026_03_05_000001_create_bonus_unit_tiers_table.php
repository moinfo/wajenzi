<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_unit_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->integer('max_units');
            $table->timestamps();
        });

        // Seed default tiers
        $tiers = [
            [0, 1000000, 1],
            [1000000, 3500000, 2],
            [3500000, 7000000, 4],
            [7000000, 12000000, 5],
            [12000000, 15000000, 7],
            [15000000, 20000000, 10],
            [20000000, 30000000, 15],
            [30000000, 40000000, 20],
            [40000000, 50000000, 25],
            [50000000, 60000000, 30],
            [60000000, 70000000, 35],
            [70000000, 80000000, 40],
            [80000000, 90000000, 45],
            [90000000, 100000000, 50],
        ];

        foreach ($tiers as $tier) {
            DB::table('bonus_unit_tiers')->insert([
                'min_amount' => $tier[0],
                'max_amount' => $tier[1],
                'max_units' => $tier[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_unit_tiers');
    }
};
