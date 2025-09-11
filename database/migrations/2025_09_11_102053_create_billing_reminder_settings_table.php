<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_reminders_enabled')->default(true);
            $table->json('reminder_intervals')->comment('Days before due date to send reminders: [28,21,14,7,3,1]');
            $table->boolean('late_fees_enabled')->default(true);
            $table->decimal('late_fee_percentage', 5, 2)->default(10.00);
            $table->boolean('late_fee_reminders_enabled')->default(true);
            $table->integer('late_fee_reminder_interval')->default(7)->comment('Days between late fee reminders');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_reminder_settings');
    }
};
