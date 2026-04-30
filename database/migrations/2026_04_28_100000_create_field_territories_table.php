<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUsersTablePrimaryKey();
        Schema::dropIfExists('field_territories');

        Schema::create('field_territories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_territories');
    }

    private function ensureUsersTablePrimaryKey(): void
    {
        if (!Schema::hasTable('users') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $hasPrimaryKey = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'users')
            ->where('index_name', 'PRIMARY')
            ->exists();

        if (!$hasPrimaryKey) {
            DB::statement('ALTER TABLE `users` ADD PRIMARY KEY (`id`)');
        }

        DB::statement('ALTER TABLE `users` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        $nextId = (DB::table('users')->max('id') ?? 0) + 1;
        DB::statement('ALTER TABLE `users` AUTO_INCREMENT = ' . max($nextId, 1));
    }
};
