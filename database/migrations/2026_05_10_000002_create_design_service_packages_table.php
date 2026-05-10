<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('design_service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Silver, Gold, Platinum
            $table->enum('rise_type', ['low', 'high']);     // low-rise or high-rise
            $table->decimal('price_usd', 10, 2);
            $table->json('included_services')->nullable();  // ["Architectural design", "BOQ preparation", ...]
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_service_packages');
    }
};
