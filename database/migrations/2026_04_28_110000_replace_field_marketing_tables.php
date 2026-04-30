<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePrimaryKey('leads');

        // Remove attribution column added to leads
        if (Schema::hasColumn('leads', 'field_activity_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['field_activity_id']);
                $table->dropColumn('field_activity_id');
            });
        }

        Schema::dropIfExists('field_activities');
        Schema::dropIfExists('field_marketing_campaigns');
        Schema::dropIfExists('field_marketing_targets');
        Schema::dropIfExists('field_marketing_visit_services');
        Schema::dropIfExists('field_marketing_visits');
        Schema::dropIfExists('field_marketing_sessions');
        Schema::dropIfExists('field_marketing_services');

        // Services — configurable list shown in visit forms
        Schema::create('field_marketing_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Sessions — one session = one officer's day of fieldwork in an area
        Schema::create('field_marketing_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_number')->unique();
            $table->foreignId('officer_id')->constrained('users');
            $table->string('area')->nullable();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Visits — individual business contacts within a session
        Schema::create('field_marketing_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('field_marketing_sessions')->cascadeOnDelete();
            $table->string('business_name');
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['interested', 'not_interested', 'follow_up', 'converted'])->default('follow_up');
            $table->date('next_followup_date')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Pivot: visits ↔ services
        Schema::create('field_marketing_visit_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('field_marketing_visits')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('field_marketing_services')->cascadeOnDelete();
            $table->unique(['visit_id', 'service_id']);
        });

        // Monthly per-officer targets
        Schema::create('field_marketing_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('users');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->integer('target_visits')->default(0);
            $table->integer('target_conversions')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['officer_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_marketing_targets');
        Schema::dropIfExists('field_marketing_visit_services');
        Schema::dropIfExists('field_marketing_visits');
        Schema::dropIfExists('field_marketing_sessions');
        Schema::dropIfExists('field_marketing_services');
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
