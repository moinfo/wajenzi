<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('design_service_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_low_usd', 10, 2)->comment('Price when added to low-rise package');
            $table->decimal('price_high_usd', 10, 2)->comment('Price when added to high-rise package');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('design_service_addons'); }
};
