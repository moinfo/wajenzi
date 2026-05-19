<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_schedule_activity_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')
                ->constrained('project_schedule_activities')
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('activity_id');
        });

        if (Schema::hasColumn('project_schedule_activities', 'attachment_path')) {
            DB::table('project_schedule_activities')
                ->whereNotNull('attachment_path')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    $now = now();
                    $inserts = [];
                    foreach ($rows as $row) {
                        $inserts[] = [
                            'activity_id'  => $row->id,
                            'path'         => $row->attachment_path,
                            'name'         => $row->attachment_name ?: basename($row->attachment_path),
                            'mime_type'    => null,
                            'size_bytes'   => null,
                            'uploaded_by'  => $row->completed_by ?? null,
                            'created_at'   => $row->completed_at ?? $now,
                            'updated_at'   => $row->completed_at ?? $now,
                        ];
                    }
                    if (!empty($inserts)) {
                        DB::table('project_schedule_activity_attachments')->insert($inserts);
                    }
                });

            Schema::table('project_schedule_activities', function (Blueprint $table) {
                $table->dropColumn(['attachment_path', 'attachment_name']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('project_schedule_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('project_schedule_activities', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('completion_notes');
            }
            if (!Schema::hasColumn('project_schedule_activities', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }
        });

        DB::statement('UPDATE project_schedule_activities psa
            INNER JOIN (
                SELECT activity_id, MIN(id) AS first_id
                FROM project_schedule_activity_attachments
                GROUP BY activity_id
            ) t ON t.activity_id = psa.id
            INNER JOIN project_schedule_activity_attachments psaa ON psaa.id = t.first_id
            SET psa.attachment_path = psaa.path, psa.attachment_name = psaa.name');

        Schema::dropIfExists('project_schedule_activity_attachments');
    }
};
