<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete any records with id = 0 if they exist
        \DB::table('account_types')->where('id', 0)->delete();
        
        // Reset the auto_increment to the next available value
        $maxId = \DB::table('account_types')->max('id') ?? 0;
        \DB::statement("ALTER TABLE account_types AUTO_INCREMENT = " . ($maxId + 1));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            //
        });
    }
};
