<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add artisan-specific fields including is_artisan flag
        // Note: ENUM modification avoided due to MySQL server restrictions
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'is_artisan')) {
                $table->boolean('is_artisan')->default(false)->after('supplier_type');
            }
            if (!Schema::hasColumn('suppliers', 'trade_skill')) {
                $table->string('trade_skill', 100)->nullable()->after('is_artisan');
            }
            if (!Schema::hasColumn('suppliers', 'daily_rate')) {
                $table->decimal('daily_rate', 15, 2)->nullable()->after('trade_skill');
            }
            if (!Schema::hasColumn('suppliers', 'id_number')) {
                $table->string('id_number', 50)->nullable()->after('daily_rate');
            }
            if (!Schema::hasColumn('suppliers', 'previous_work_history')) {
                $table->text('previous_work_history')->nullable()->after('id_number');
            }
            if (!Schema::hasColumn('suppliers', 'rating')) {
                $table->decimal('rating', 3, 2)->nullable()->after('previous_work_history');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = ['is_artisan', 'trade_skill', 'daily_rate', 'id_number', 'previous_work_history', 'rating'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
