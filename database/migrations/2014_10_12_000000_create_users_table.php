<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->nullable();
            $table->enum('type', ['STAFF', 'INTERN', 'EXTERNAL'])->default('STAFF');
            $table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->default('MALE');
            $table->date('dob')->nullable();
            $table->string('employee_number')->nullable();
            $table->string('national_id')->nullable();
            $table->string('tin')->nullable();
            $table->string('recruitment_date')->nullable();
            $table->enum('marital_status', ['SINGLE', 'MARRIED', 'DIVORCED', 'OTHER'])->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->integer('avatar_id')->nullable();
            $table->integer('recruitment_applicant_id')->nullable();
            $table->date('employment_date')->nullable()->useCurrent();
            $table->enum('employment_type', ['FULL_TIME', 'CONTRACT', 'TEMPORARY', 'INTERN'])->default('FULL_TIME');
            $table->enum('status', ['ACTIVE', 'DORMANT', 'INACTIVE'])->default('ACTIVE');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
