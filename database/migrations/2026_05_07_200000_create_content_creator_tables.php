<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_creator_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('deadline')->nullable();
            $table->time('deadline_time')->nullable();
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'published'])->default('todo');
            $table->enum('progress', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->enum('platform', ['instagram', 'tiktok', 'facebook', 'linkedin', 'youtube', 'general'])->default('general');
            $table->enum('task_type', ['video_shoot', 'post_publish', 'design_task', 'review_approval', 'other'])->default('other');
            $table->json('attachments')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_creator_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('content_creator_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('content_creator_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->enum('task_type', ['video_shoot', 'post_publish', 'design_task', 'review_approval', 'day_off'])->default('video_shoot');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('content_creator_platform_targets', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['instagram', 'tiktok', 'facebook', 'linkedin', 'youtube']);
            $table->unsignedInteger('target_posts')->default(0);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unique(['platform', 'month', 'year']);
            $table->timestamps();
        });

        Schema::create('content_creator_crew', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('Creator');
            $table->json('skills')->nullable();
            $table->enum('online_status', ['online', 'busy', 'away', 'offline'])->default('offline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_creator_crew');
        Schema::dropIfExists('content_creator_platform_targets');
        Schema::dropIfExists('content_creator_schedules');
        Schema::dropIfExists('content_creator_task_comments');
        Schema::dropIfExists('content_creator_tasks');
    }
};