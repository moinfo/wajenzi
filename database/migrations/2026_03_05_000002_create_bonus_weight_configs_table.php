<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_weight_configs', function (Blueprint $table) {
            $table->id();
            $table->string('factor'); // schedule, quality, client
            $table->decimal('weight', 3, 2); // e.g. 0.40
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default weights (40/40/20)
        DB::table('bonus_weight_configs')->insert([
            ['factor' => 'schedule', 'weight' => 0.40, 'description' => 'Schedule Performance (SP)', 'created_at' => now(), 'updated_at' => now()],
            ['factor' => 'quality', 'weight' => 0.40, 'description' => 'Design Quality (DQ)', 'created_at' => now(), 'updated_at' => now()],
            ['factor' => 'client', 'weight' => 0.20, 'description' => 'Client Approval Efficiency (CA)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_weight_configs');
    }
};
