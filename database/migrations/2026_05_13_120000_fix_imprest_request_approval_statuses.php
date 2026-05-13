<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'process_approval_statuses';
        $type  = 'App\\Models\\ImprestRequest';

        $duplicateIds = DB::table($table . ' as a')
            ->join($table . ' as b', function ($join) {
                $join->on('a.approvable_id', '=', 'b.approvable_id')
                    ->on('a.approvable_type', '=', 'b.approvable_type')
                    ->whereColumn('a.id', '>', 'b.id');
            })
            ->where('a.approvable_type', $type)
            ->pluck('a.id')
            ->unique()
            ->all();

        if (!empty($duplicateIds)) {
            DB::table($table)->whereIn('id', $duplicateIds)->delete();
        }

        DB::table($table)
            ->where('approvable_type', $type)
            ->where('status', 'Created')
            ->update(['status' => 'Submitted', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible cleanup of duplicate approval status rows; no down step.
    }
};
