<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'proforma')) {
                $table->string('proforma')->nullable()->after('mobile_number');
            }
            if (! Schema::hasColumn('suppliers', 'quotation')) {
                $table->string('quotation')->nullable()->after('proforma');
            }
            if (! Schema::hasColumn('suppliers', 'document')) {
                $table->string('document')->nullable()->after('quotation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            foreach (['document', 'quotation', 'proforma'] as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
