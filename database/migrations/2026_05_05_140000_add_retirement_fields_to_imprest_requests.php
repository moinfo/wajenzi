<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imprest_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('imprest_requests', 'retirement_file')) {
                $table->string('retirement_file')->nullable()->after('file');
            }
            if (!Schema::hasColumn('imprest_requests', 'retirement_notes')) {
                $table->text('retirement_notes')->nullable()->after('retirement_file');
            }
            if (!Schema::hasColumn('imprest_requests', 'retired_at')) {
                $table->timestamp('retired_at')->nullable()->after('retirement_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('imprest_requests', function (Blueprint $table) {
            foreach (['retirement_file', 'retirement_notes', 'retired_at'] as $col) {
                if (Schema::hasColumn('imprest_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
