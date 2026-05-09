<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!\Spatie\Permission\Models\Role::where('name', 'Service Engineer')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'Service Engineer', 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        \Spatie\Permission\Models\Role::where('name', 'Service Engineer')->delete();
    }
};
