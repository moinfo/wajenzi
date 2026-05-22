<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting evidence files attached to a KPI review or to a specific rating row.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_review_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_review_rating_id')->nullable()->constrained('kpi_review_ratings')->cascadeOnDelete();
            $table->string('file_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_review_attachments');
    }
};
