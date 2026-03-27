<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsCatalogApiController extends Controller
{
    private function resolveSettingUrl(?string $route): ?string
    {
        if (blank($route)) {
            return null;
        }

        try {
            return route($route);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function settingsCatalog(): array
    {
        return [
            ['name' => 'Approval Flows', 'route' => 'hr_settings_process_approval_flows', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Approval Flow Step', 'route' => 'hr_settings_process_approval_flow_steps', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Staff Allowances', 'route' => 'hr_settings_allowances', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Allowance Subscriptions', 'route' => 'allowance_subscriptions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Staff Salary', 'route' => 'hr_settings_staff_salary', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Advance Salary', 'route' => 'hr_settings_advance_salary', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Staff Loan', 'route' => 'hr_settings_staff_loan', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Deductions', 'route' => 'hr_settings_deductions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Deduction Subscriptions', 'route' => 'hr_settings_deduction_subscriptions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Deduction Settings', 'route' => 'hr_settings_deduction_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Departments', 'route' => 'hr_settings_departments', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Banks', 'route' => 'hr_settings_banks', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Service Interesteds', 'route' => 'hr_settings_service_interesteds', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Lead Statuses', 'route' => 'hr_settings_lead_statuses', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Lead Sources', 'route' => 'hr_settings_lead_sources', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Project Types', 'route' => 'hr_settings_project_types_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Service Types', 'route' => 'hr_settings_service_types', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Project Statuses', 'route' => 'hr_settings_project_statuses', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Cost Categories', 'route' => 'hr_settings_cost_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Assets', 'route' => 'hr_settings_assets', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Asset Properties', 'route' => 'hr_settings_asset_properties', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Systems', 'route' => 'hr_settings_systems', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Users', 'route' => 'hr_settings_users', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Positions', 'route' => 'hr_settings_positions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Roles', 'route' => 'hr_settings_roles', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Permissions', 'route' => 'hr_settings_permissions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Suppliers', 'route' => 'hr_settings_suppliers', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Items', 'route' => 'hr_settings_items', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Expenses Categories', 'route' => 'hr_settings_expenses_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Expenses Sub Categories', 'route' => 'hr_settings_expenses_sub_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Financial Charge Categories', 'route' => 'hr_settings_financial_charge_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'EFD', 'route' => 'hr_settings_efd', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Approval Document Type', 'route' => 'hr_settings_approval_document_types', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Approval', 'route' => 'hr_settings_approvals', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Approval Level', 'route' => 'hr_settings_approval_levels', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'User Group', 'route' => 'hr_settings_user_groups', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Assign User Group', 'route' => 'hr_settings_assign_user_groups', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Statutory Payment', 'route' => 'hr_settings_statutory_payments', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Statutory Payment Category', 'route' => 'hr_settings_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Statutory Payment Sub Category', 'route' => 'hr_settings_sub_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'System Settings', 'route' => 'system_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Client Sources', 'route' => 'client_sources', 'icon' => 'si si-settings', 'badge' => 0],
            ['name' => 'Building Types', 'route' => 'hr_settings_building_types', 'icon' => 'si si-home', 'badge' => 0],
            ['name' => 'BOQ Item Categories', 'route' => 'hr_settings_boq_item_categories', 'icon' => 'si si-list', 'badge' => 0],
            ['name' => 'Construction Stages', 'route' => 'hr_settings_construction_stages', 'icon' => 'si si-layers', 'badge' => 0],
            ['name' => 'Activities', 'route' => 'hr_settings_activities', 'icon' => 'si si-wrench', 'badge' => 0],
            ['name' => 'Sub-Activities', 'route' => 'hr_settings_sub_activities', 'icon' => 'si si-puzzle', 'badge' => 0],
            ['name' => 'BOQ Items', 'route' => 'hr_settings_boq_items', 'icon' => 'si si-bag', 'badge' => 0],
            ['name' => 'BOQ Templates', 'route' => 'hr_settings_boq_templates', 'icon' => 'si si-docs', 'badge' => 0],
            ['name' => 'Activity Templates', 'route' => 'hr_settings_activity_templates', 'icon' => 'si si-calendar', 'badge' => 0],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = collect($this->settingsCatalog())
            ->filter(fn (array $setting) => $user->can($setting['name']))
            ->map(fn (array $setting) => [
                'name' => $setting['name'],
                'route' => $setting['route'],
                'icon' => $setting['icon'],
                'badge' => $setting['badge'],
                'url' => $this->resolveSettingUrl($setting['route']),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'total' => $settings->count(),
            ],
        ]);
    }
}
