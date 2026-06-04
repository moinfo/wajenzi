<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Site Paylog — one row per daily payment made on a site, covering both
     * material and labour spend. Flat (not nested under a daily report) so that
     * "pull daily" and the monthly report are simple whereDate / whereMonth
     * group-bys. Anchored on site_id; project_id is an optional BOQ link.
     */
    public function up(): void
    {
        Schema::create('site_paylogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('payment_date');
            $table->enum('category', ['material', 'labour'])->default('material');
            $table->string('payee_name');                       // e.g. "Juma Mason"
            $table->string('reason');                           // e.g. "Cement 50 bags / Labour 3 days"
            $table->foreignId('payment_channel_id')->nullable()->constrained('payment_channels')->nullOnDelete();
            $table->string('account_name')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('SUBMITTED');     // log-and-submit, no approval chain
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'payment_date']);
            $table->index('payment_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_paylogs');
    }
};
