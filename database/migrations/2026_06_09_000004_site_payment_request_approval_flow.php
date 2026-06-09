<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Wire the RingleSoft approval flow, permission and menu for Site Payment
     * Requests:
     *   Step 1  Procurement Officer  — VERIFY
     *   Step 2  Managing Director    — APPROVE
     * (Finance/Accountant records the payment after approval via the
     *  "Process Site Payment" permission — not a RingleSoft step.)
     *
     * Idempotent: safe to re-run. Looks roles up by name, skips gracefully when
     * a role is missing in an environment.
     */
    private string $approvableType = 'App\\Models\\SitePaymentRequest';

    public function up(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');
        $procurement = $roles['Procurement Officer'] ?? null;
        $md          = $roles['Managing Director'] ?? null;
        $accountant  = $roles['Accountant'] ?? null;
        $admin       = $roles['System Administrator'] ?? null;

        // ── Approval flow + steps ────────────────────────────────────────────
        if ($md) {
            $flow = DB::table('process_approval_flows')
                ->where('approvable_type', $this->approvableType)->first();

            if ($flow) {
                $flowId = $flow->id;
            } else {
                $flowId = (DB::table('process_approval_flows')->max('id') ?? 0) + 1;
                DB::table('process_approval_flows')->insert([
                    'id'              => $flowId,
                    'name'            => 'SitePaymentRequest',
                    'approvable_type' => $this->approvableType,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $steps = array_values(array_filter([
                $procurement ? ['role_id' => $procurement, 'action' => 'VERIFY',  'order' => 1] : null,
                ['role_id' => $md, 'action' => 'APPROVE', 'order' => 2],
            ]));

            foreach ($steps as $step) {
                $exists = DB::table('process_approval_flow_steps')
                    ->where('process_approval_flow_id', $flowId)
                    ->where('role_id', $step['role_id'])
                    ->where('action', $step['action'])
                    ->exists();

                if (!$exists) {
                    $stepId = (DB::table('process_approval_flow_steps')->max('id') ?? 0) + 1;
                    DB::table('process_approval_flow_steps')->insert([
                        'id'                       => $stepId,
                        'process_approval_flow_id' => $flowId,
                        'role_id'                  => $step['role_id'],
                        'order'                    => $step['order'],
                        'action'                   => $step['action'],
                        'active'                   => 1,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);
                }
            }
        }

        // ── Permission: Process Site Payment (Finance step) ──────────────────
        $permName = 'Process Site Payment';
        $perm = DB::table('permissions')->where('name', $permName)->first();
        if (!$perm) {
            $permId = (DB::table('permissions')->max('id') ?? 0) + 1;
            DB::table('permissions')->insert([
                'id'         => $permId,
                'name'       => $permName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permId = $perm->id;
        }

        foreach (array_filter([$accountant, $admin, $md]) as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id'       => $roleId,
            ]);
        }

        // ── Menu: "Payment Requests" under the existing Site Paylog parent ────
        $parent = DB::table('menus')->where('name', 'Site Paylog')->first();
        if ($parent && !DB::table('menus')->where('name', 'Payment Requests')->exists()) {
            $menuId = (DB::table('menus')->max('id') ?? 0) + 1;
            DB::table('menus')->insert([
                'id'         => $menuId,
                'name'       => 'Payment Requests',
                'route'      => 'site_paylog.requests',
                'icon'       => 'fa fa-file-invoice-dollar',
                'parent_id'  => $parent->id,
                'list_order' => 5,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', $this->approvableType)->first();
        if ($flow) {
            DB::table('process_approval_flow_steps')->where('process_approval_flow_id', $flow->id)->delete();
            DB::table('process_approval_flows')->where('id', $flow->id)->delete();
        }

        $perm = DB::table('permissions')->where('name', 'Process Site Payment')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }

        DB::table('menus')->where('name', 'Payment Requests')->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
