<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('symbol');
            $table->decimal('rate_to_usd', 15, 6)->default(1)->after('code')->comment('Units of this currency per 1 USD');
            $table->boolean('is_active')->default(true)->after('rate_to_usd');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['code', 'rate_to_usd', 'is_active']);
        });
    }
};
