<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePrimaryKey('lead_sources');
        Schema::dropIfExists('field_marketing_campaigns');

        Schema::create('field_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('campaign_type', ['outdoor', 'event', 'canvassing', 'demo', 'referral', 'other'])->default('outdoor');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->integer('target_leads')->default(0);
            $table->foreignId('territory_id')->nullable()->constrained('field_territories')->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_marketing_campaigns');
    }

    private function ensurePrimaryKey(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || DB::getDriverName() !== 'mysql') {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $hasPrimaryKey = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('index_name', 'PRIMARY')
            ->exists();

        if (!$hasPrimaryKey) {
            DB::statement("ALTER TABLE `{$tableName}` ADD PRIMARY KEY (`id`)");
        }

        DB::statement("ALTER TABLE `{$tableName}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");

        $nextId = (DB::table($tableName)->max('id') ?? 0) + 1;
        DB::statement("ALTER TABLE `{$tableName}` AUTO_INCREMENT = " . max($nextId, 1));
    }
};
