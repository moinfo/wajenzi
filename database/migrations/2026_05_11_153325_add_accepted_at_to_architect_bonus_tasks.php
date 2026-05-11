<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('architect_bonus_tasks', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('architect_bonus_tasks', function (Blueprint $table) {
            $table->dropColumn('accepted_at');
        });
    }
};