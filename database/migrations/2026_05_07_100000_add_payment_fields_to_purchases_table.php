<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'payment_status')) {
                $table->string('payment_status', 20)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('purchases', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('purchases', 'payment_reference')) {
                $table->string('payment_reference', 100)->nullable()->after('payment_date');
            }
            if (!Schema::hasColumn('purchases', 'payment_attachment')) {
                $table->string('payment_attachment')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('purchases', 'payment_note')) {
                $table->text('payment_note')->nullable()->after('payment_attachment');
            }
            if (!Schema::hasColumn('purchases', 'payment_uploaded_by')) {
                $table->unsignedBigInteger('payment_uploaded_by')->nullable()->after('payment_note');
            }
        });

        // Permissions
        $permissions = [
            'Upload Payment Attachment' => 'CRUD',
            'Close Purchase Order'      => 'CRUD',
        ];

        foreach ($permissions as $name => $type) {
            if (!DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name'            => $name,
                    'guard_name'      => 'web',
                    'permission_type' => $type,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        // Grant "Upload Payment Attachment" to Accountant, System Administrator, Managing Director
        $uploadPerm = DB::table('permissions')->where('name', 'Upload Payment Attachment')->first();
        if ($uploadPerm) {
            $uploadRoles = DB::table('roles')
                ->whereIn('name', ['Accountant', 'System Administrator', 'Managing Director'])
                ->pluck('id');
            foreach ($uploadRoles as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $uploadPerm->id,
                ]);
            }
        }

        // Grant "Close Purchase Order" to Procurement Officer, System Administrator, Managing Director
        $closePerm = DB::table('permissions')->where('name', 'Close Purchase Order')->first();
        if ($closePerm) {
            $closeRoles = DB::table('roles')
                ->whereIn('name', ['Procurement Officer', 'System Administrator', 'Managing Director'])
                ->pluck('id');
            foreach ($closeRoles as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $closePerm->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $columns = [
                'payment_status', 'payment_date', 'payment_reference',
                'payment_attachment', 'payment_note', 'payment_uploaded_by',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        foreach (['Upload Payment Attachment', 'Close Purchase Order'] as $name) {
            $perm = DB::table('permissions')->where('name', $name)->first();
            if ($perm) {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('permissions')->where('id', $perm->id)->delete();
            }
        }
    }
};