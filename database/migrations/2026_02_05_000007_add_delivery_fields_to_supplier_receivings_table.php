<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('supplier_receivings', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_receivings', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('supplier_receivings', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('purchase_id');
            }

            if (!Schema::hasColumn('supplier_receivings', 'delivery_note_number')) {
                $table->string('delivery_note_number', 50)->nullable()->after('supplier_id');
            }

            if (!Schema::hasColumn('supplier_receivings', 'receiving_number')) {
                $table->string('receiving_number', 50)->nullable()->after('id');
            }

            if (!Schema::hasColumn('supplier_receivings', 'quantity_ordered')) {
                $table->decimal('quantity_ordered', 10, 2)->nullable()->after('amount');
            }

            if (!Schema::hasColumn('supplier_receivings', 'quantity_delivered')) {
                $table->decimal('quantity_delivered', 10, 2)->nullable()->after('quantity_ordered');
            }

            if (!Schema::hasColumn('supplier_receivings', 'condition')) {
                $table->enum('condition', ['good', 'damaged', 'partial_damage'])->default('good')->after('quantity_delivered');
            }

            if (!Schema::hasColumn('supplier_receivings', 'supplier_signature')) {
                $table->string('supplier_signature')->nullable()->after('description');
            }

            if (!Schema::hasColumn('supplier_receivings', 'supervisor_signature')) {
                $table->string('supervisor_signature')->nullable()->after('supplier_signature');
            }

            if (!Schema::hasColumn('supplier_receivings', 'technician_signature')) {
                $table->string('technician_signature')->nullable()->after('supervisor_signature');
            }

            if (!Schema::hasColumn('supplier_receivings', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('supplier_id');
            }

            if (!Schema::hasColumn('supplier_receivings', 'status')) {
                $table->enum('status', ['pending', 'received', 'inspected', 'rejected'])
                    ->default('pending')->after('condition');
            }
        });

        Schema::table('supplier_receivings', function (Blueprint $table) {
            if (!$this->hasForeignKey('supplier_receivings', 'supplier_receivings_purchase_id_foreign')) {
                $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            }
            if (!$this->hasForeignKey('supplier_receivings', 'supplier_receivings_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            }
            if (!$this->hasForeignKey('supplier_receivings', 'supplier_receivings_received_by_foreign')) {
                $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('supplier_receivings', function (Blueprint $table) {
            if (!$this->hasIndex('supplier_receivings', 'supplier_receivings_receiving_number_index')) {
                $table->index('receiving_number');
            }
            if (!$this->hasIndex('supplier_receivings', 'supplier_receivings_delivery_note_number_index')) {
                $table->index('delivery_note_number');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function hasForeignKey(string $table, string $keyName): bool
    {
        $keys = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table]);

        return collect($keys)->contains(fn($key) => $key->CONSTRAINT_NAME === $keyName);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_receivings', function (Blueprint $table) {
            // Drop foreign keys first
            $foreignKeys = ['purchase_id', 'project_id', 'received_by'];

            foreach ($foreignKeys as $column) {
                if (Schema::hasColumn('supplier_receivings', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }

            // Drop other columns
            $columns = [
                'delivery_note_number', 'receiving_number', 'quantity_ordered',
                'quantity_delivered', 'condition', 'supplier_signature',
                'supervisor_signature', 'technician_signature', 'status'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('supplier_receivings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
