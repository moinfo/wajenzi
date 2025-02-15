<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveManagementTables extends Migration
{
    public function up()
    {
        // Recreate leave_types table
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('days_allowed')->default(0);
            $table->text('description')->nullable();
            $table->integer('notice_days')->default(1);
            $table->timestamps();
        });

        // Recreate leave_requests table
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // First drop leave_requests table because it has foreign keys
        Schema::dropIfExists('leave_requests');

        // Then drop leave_types table
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('leave_plans');
        Schema::dropIfExists('staff_leaves');
    }


}
