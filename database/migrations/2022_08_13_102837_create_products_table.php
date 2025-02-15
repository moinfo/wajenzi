<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sub_category_id');
            $table->integer('billing_cycle');
            $table->date('due_date');
            $table->date('issue_date');
            $table->decimal('amount',20,2);
            $table->text('description')->nullable();
            $table->enum('status', ['UNPAID','CANCELLED','INACTIVE','REJECTED','PAID','CREATED','EXPIRED','ACTIVE'])->default('UNPAID');
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
        Schema::dropIfExists('products');
    }
}
