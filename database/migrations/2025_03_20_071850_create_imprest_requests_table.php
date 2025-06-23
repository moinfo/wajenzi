<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImprestRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imprest_requests', function (Blueprint $table) {
            $table->id();
            $table->string('document_number');
            $table->text('description');
            $table->decimal('amount',20,2);
            $table->enum('status',['CREATED','PENDING','APPROVED','REJECTED','REFILLED'])->default('CREATED');
            $table->integer('create_by_id')->default(1);
            $table->integer('expenses_sub_category_id');
            $table->string('file')->nullable();
            $table->date('date');
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
        Schema::dropIfExists('imprest_requests');
    }
}
