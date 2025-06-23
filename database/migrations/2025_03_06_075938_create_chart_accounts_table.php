<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChartAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charts_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('account_name', 255);
            $table->integer('account_type')->default(5)->nullable();
            $table->integer('currency')->default(1);
            $table->integer('parent')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamp('entry_timestamp')->useCurrent();
            $table->timestamps();


            $table->unique('code');

            // Foreign key if needed
          //  $table->foreign('account_type')->references('id')->on('account_types');
            //$table->foreign('parent')->references('id')->on('charts_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_accounts');
    }
}
