<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Department;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            // Add department_id foreign key
            $table->foreignId('department_id')->nullable()->constrained('departments')->after('prepared_by');
        });

        // Set default department for existing records
        $salesDepartment = Department::where('name', 'like', '%Sales%')->first();
        if ($salesDepartment) {
            DB::table('sales_daily_reports')
                ->whereNull('department_id')
                ->update(['department_id' => $salesDepartment->id]);
        }
    }

    public function down()
    {
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};