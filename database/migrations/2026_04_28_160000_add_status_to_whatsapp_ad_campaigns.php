<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ad_campaigns', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ad_campaigns', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
