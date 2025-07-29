<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SalesDailyReportApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create approval document type for Sales Daily Report
        $documentType = DB::table('approval_document_types')->updateOrInsert(
            ['keyword' => 'sales_daily_report'],
            [
                'keyword' => 'sales_daily_report',
                'name' => 'Sales Daily Report',
                'description' => 'Daily report for sales and business development activities',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        $documentTypeId = DB::table('approval_document_types')
            ->where('keyword', 'sales_daily_report')
            ->first()->id;

        // Check if we have user groups for approvers
        $adminUserGroup = DB::table('user_groups')->where('name', 'like', '%admin%')->orWhere('name', 'like', '%manager%')->first();
        
        if (!$adminUserGroup) {
            // Create a user group for Sales Daily Report approvers
            $adminUserGroupId = DB::table('user_groups')->insertGetId([
                'name' => 'Sales Report Approvers',
                'description' => 'Users who can approve sales daily reports',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $adminUserGroupId = $adminUserGroup->id;
        }

        // Create approval level
        DB::table('approval_levels')->updateOrInsert(
            [
                'approval_document_types_id' => $documentTypeId,
                'order' => 1
            ],
            [
                'approval_document_types_id' => $documentTypeId,
                'order' => 1,
                'user_group_id' => $adminUserGroupId,
                'description' => 'Sales Daily Report Approval',
                'action' => 'APPROVE',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Assign users to the user group if we have suitable users
        $managingDirector = User::whereHas('roles', function($query) {
            $query->where('name', 'Managing Director');
        })->first();

        $systemAdmin = User::whereHas('roles', function($query) {
            $query->where('name', 'System Administrator');
        })->first();

        if ($systemAdmin) {
            DB::table('assign_user_groups')->updateOrInsert(
                ['user_id' => $systemAdmin->id, 'user_group_id' => $adminUserGroupId],
                [
                    'user_id' => $systemAdmin->id,
                    'user_group_id' => $adminUserGroupId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        if ($managingDirector) {
            DB::table('assign_user_groups')->updateOrInsert(
                ['user_id' => $managingDirector->id, 'user_group_id' => $adminUserGroupId],
                [
                    'user_id' => $managingDirector->id,
                    'user_group_id' => $adminUserGroupId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        if ($systemAdmin || $managingDirector) {
            $this->command->info('Created approval workflow and assigned approvers for Sales Daily Reports');
        } else {
            $this->command->warn('No suitable approvers found. Please assign users to the Sales Report Approvers group manually.');
        }

        // Create process approval flow
        DB::table('process_approval_flows')->updateOrInsert(
            ['approvable_type' => 'App\\Models\\SalesDailyReport'],
            [
                'name' => 'Sales Daily Report Approval Flow',
                'approvable_type' => 'App\\Models\\SalesDailyReport',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        $this->command->info('Sales Daily Report approval workflow configured successfully!');
    }
}