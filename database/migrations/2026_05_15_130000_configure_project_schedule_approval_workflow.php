<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure "General Manager" role exists. Spatie roles use guard_name = 'web' by default.
        $gmRoleId = DB::table('roles')->where('name', 'General Manager')->value('id');
        if (!$gmRoleId) {
            $gmRoleId = DB::table('roles')->insertGetId([
                'name' => 'General Manager',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\ProjectSchedule')
            ->first();

        if (!$flow) {
            return; // base flow migration must run first
        }

        // Wipe existing steps for a clean, ordered workflow: CEO -> MD -> GM
        DB::table('process_approval_flow_steps')
            ->where('process_approval_flow_id', $flow->id)
            ->delete();

        $steps = [
            ['role_id' => 12, 'order' => 1, 'action' => 'APPROVE'], // Chief Executive Officer
            ['role_id' => 2,  'order' => 2, 'action' => 'APPROVE'], // Managing Director
            ['role_id' => $gmRoleId, 'order' => 3, 'action' => 'APPROVE'], // General Manager
        ];

        foreach ($steps as $s) {
            DB::table('process_approval_flow_steps')->insert([
                'process_approval_flow_id' => $flow->id,
                'role_id' => $s['role_id'],
                'order' => $s['order'],
                'action' => $s['action'],
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\ProjectSchedule')
            ->first();

        if ($flow) {
            DB::table('process_approval_flow_steps')
                ->where('process_approval_flow_id', $flow->id)
                ->delete();

            // Restore the single MD step from the prior migration
            DB::table('process_approval_flow_steps')->insert([
                'process_approval_flow_id' => $flow->id,
                'role_id' => 2,
                'order' => 1,
                'action' => 'APPROVE',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
