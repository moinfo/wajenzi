<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_types')) {
            return;
        }

        $groups = DB::table('project_types')
            ->select('name', 'description', DB::raw('COUNT(*) as total'))
            ->groupBy('name', 'description')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('project_types')
                ->where('name', $group->name)
                ->where(function ($query) use ($group) {
                    if ($group->description === null) {
                        $query->whereNull('description');
                    } else {
                        $query->where('description', $group->description);
                    }
                })
                ->orderBy('id')
                ->get(['id']);

            if ($rows->count() < 2) {
                continue;
            }

            $keepId = (int) $rows->first()->id;
            $duplicateIds = $rows->slice(1)->pluck('id')->map(fn ($id) => (int) $id)->all();

            if (empty($duplicateIds)) {
                continue;
            }

            if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'project_type_id')) {
                DB::table('projects')
                    ->whereIn('project_type_id', $duplicateIds)
                    ->update(['project_type_id' => $keepId]);
            }

            DB::table('project_types')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Duplicate cleanup is intentionally irreversible.
    }
};
