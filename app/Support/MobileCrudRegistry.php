<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetProperty;
use App\Models\AssignUserGroup;
use App\Models\Bank;
use App\Models\Category;
use App\Models\ClientSource;
use App\Models\ProjectActivityTemplate;
use App\Models\SubCategory;
use App\Models\Supervisor;
use App\Models\System;
use App\Models\SystemSetting;
use App\Models\AdjustedAssessmentTax;
use App\Models\AdjustmentExpense;
use App\Models\Collection;
use App\Models\Efd;
use App\Models\FinancialCharge;
use App\Models\FinancialChargeCategory;
use App\Models\Gross;
use App\Models\ProjectInvoice;
use App\Models\ProjectPayment;
use App\Models\Receiving;
use App\Models\Supplier;
use App\Models\SupplierReceiving;
use App\Models\SystemCapital;
use App\Models\SystemCash;
use App\Models\SystemCredit;
use App\Models\SystemInventory;
use App\Models\TransactionMovement;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\WithholdingTax;

/**
 * Pages of the web Settings area that the mobile app shows through the generic
 * CRUD screen. Each entry mirrors the web page: the same model, the same form
 * fields (as in resources/views/forms/*) and the same permission names the web
 * checks around its Add / Edit / Delete buttons.
 *
 *  - 'tile'   permission that shows the page (the Settings tile name)
 *  - 'perm'   Add / Edit / Delete permissions
 *  - 'fields' form fields; type: text | textarea | number | select | boolean
 *  - 'list'   fields shown on each row (first one is the title)
 */
class MobileCrudRegistry
{
    public static function all(): array
    {
        return [
            'supervisors' => [
                'title' => 'Supervisors',
                'model' => Supervisor::class,
                'tile' => 'Supervisor',
                'perm' => ['add' => 'Add Supervisor', 'edit' => 'Edit Supervisor', 'delete' => 'Delete Supervisor'],
                'order' => 'name',
                'list' => ['name', 'phone', 'employee_id', 'system_id', 'details'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'phone', 'label' => 'Phone Number', 'type' => 'text', 'required' => true],
                    ['name' => 'details', 'label' => 'Other Details', 'type' => 'textarea'],
                    ['name' => 'employee_id', 'label' => 'Employee Type', 'type' => 'select', 'required' => true,
                        'options' => [['value' => '1', 'label' => 'Supervisor'], ['value' => '2', 'label' => 'Driver']]],
                    ['name' => 'system_id', 'label' => 'System', 'type' => 'select', 'required' => true,
                        'source' => ['model' => System::class, 'value' => 'id', 'label' => 'name']],
                ],
            ],
            'banks' => [
                'title' => 'Banks',
                'model' => Bank::class,
                'tile' => 'Banks',
                'perm' => ['add' => 'Add Bank', 'edit' => 'Edit Bank', 'delete' => 'Delete Bank'],
                'order' => 'name',
                'list' => ['name', 'description'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
                ],
            ],
            'assets' => [
                'title' => 'Assets',
                'model' => Asset::class,
                'tile' => 'Assets',
                'perm' => ['add' => 'Add Asset', 'edit' => 'Edit Asset', 'delete' => 'Delete Asset'],
                'order' => 'name',
                'list' => ['name', 'description'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'asset-properties' => [
                'title' => 'Asset Properties',
                'model' => AssetProperty::class,
                'tile' => 'Asset Properties',
                'perm' => ['add' => 'Add Asset Property', 'edit' => 'Edit Asset Property', 'delete' => 'Delete Asset Property'],
                'order' => 'name',
                'list' => ['name', 'asset_id', 'user_id', 'description'],
                'fields' => [
                    ['name' => 'asset_id', 'label' => 'Asset', 'type' => 'select', 'required' => true,
                        'source' => ['model' => Asset::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'name', 'label' => 'Property Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'user_id', 'label' => 'Staff', 'type' => 'select', 'required' => true,
                        'source' => ['model' => User::class, 'value' => 'id', 'label' => 'name']],
                ],
            ],
            'user-groups' => [
                'title' => 'User Groups',
                'model' => UserGroup::class,
                'tile' => 'User Group',
                'perm' => ['add' => 'Add User Group', 'edit' => 'Edit User Group', 'delete' => 'Delete User Group'],
                'order' => 'name',
                'list' => ['name', 'keyword'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'keyword', 'label' => 'Keyword', 'type' => 'textarea'],
                ],
            ],
            'assign-user-groups' => [
                'title' => 'Assign User Group',
                'model' => AssignUserGroup::class,
                'tile' => 'Assign User Group',
                'perm' => ['add' => 'Add Assign User Group', 'edit' => 'Edit Assign User Group', 'delete' => 'Delete Assign User Group'],
                'order' => 'id',
                'list' => ['user_id', 'user_group_id'],
                'fields' => [
                    ['name' => 'user_id', 'label' => 'Staff', 'type' => 'select', 'required' => true,
                        'source' => ['model' => User::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'user_group_id', 'label' => 'User Group', 'type' => 'select', 'required' => true,
                        'source' => ['model' => UserGroup::class, 'value' => 'id', 'label' => 'name']],
                ],
            ],
            'statutory-categories' => [
                'title' => 'Statutory Payment Categories',
                'model' => Category::class,
                'tile' => 'Statutory Payment Category',
                'perm' => ['add' => 'Add Statutory Payment Category', 'edit' => 'Edit Statutory Payment Category', 'delete' => 'Delete Statutory Payment Category'],
                'order' => 'name',
                'list' => ['name', 'description'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'statutory-sub-categories' => [
                'title' => 'Statutory Payment Sub Categories',
                'model' => SubCategory::class,
                'tile' => 'Statutory Payment Sub Category',
                'perm' => ['add' => 'Add Statutory Payment Sub Category', 'edit' => 'Edit Statutory Payment Sub Category', 'delete' => 'Delete Statutory Payment Sub Category'],
                'order' => 'name',
                'list' => ['name', 'category_id', 'billing_cycle', 'price', 'description'],
                'fields' => [
                    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'required' => true,
                        'source' => ['model' => Category::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'name', 'label' => 'Sub Category', 'type' => 'text', 'required' => true],
                    ['name' => 'billing_cycle', 'label' => 'Billing Cycle', 'type' => 'select', 'required' => true,
                        'options' => [
                            ['value' => '0', 'label' => 'One Time'],
                            ['value' => '1', 'label' => 'Annually'],
                            ['value' => '3', 'label' => 'Quarterly'],
                            ['value' => '6', 'label' => 'Semi-Annually'],
                            ['value' => '12', 'label' => 'Monthly'],
                        ]],
                    ['name' => 'price', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'activity-templates' => [
                'title' => 'Activity Templates',
                'model' => ProjectActivityTemplate::class,
                'tile' => 'Activity Templates',
                'perm' => ['add' => 'Add Activity Template', 'edit' => 'Edit Activity Template', 'delete' => 'Delete Activity Template'],
                'order' => 'sort_order',
                'list' => ['name', 'activity_code', 'phase', 'discipline', 'duration_days', 'predecessor_code', 'is_active'],
                'fields' => [
                    ['name' => 'activity_code', 'label' => 'Activity Code', 'type' => 'text', 'required' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'phase', 'label' => 'Phase', 'type' => 'text', 'required' => true],
                    ['name' => 'discipline', 'label' => 'Discipline', 'type' => 'text', 'required' => true],
                    ['name' => 'duration_days', 'label' => 'Duration (Days)', 'type' => 'number', 'required' => true],
                    ['name' => 'predecessor_code', 'label' => 'Predecessor', 'type' => 'select',
                        'source' => ['model' => ProjectActivityTemplate::class, 'value' => 'activity_code', 'label' => 'name', 'prefix' => 'activity_code']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'default' => true],
                ],
            ],
            'client-sources' => [
                'title' => 'Client Sources',
                'model' => ClientSource::class,
                'tile' => 'Client Sources',
                'perm' => ['add' => 'Add Client Source', 'edit' => 'Edit Client Source', 'delete' => 'Delete Client Source'],
                'order' => 'name',
                'list' => ['name', 'description'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'system-settings' => [
                'title' => 'System Settings',
                'model' => SystemSetting::class,
                'tile' => 'System Settings',
                'perm' => ['add' => 'Add System Setting', 'edit' => 'Edit System Setting', 'delete' => 'Delete System Setting'],
                'order' => 'key',
                'list' => ['key', 'value', 'previous_value', 'description'],
                'fields' => [
                    ['name' => 'key', 'label' => 'Key', 'type' => 'text', 'required' => true],
                    ['name' => 'value', 'label' => 'Value', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'project-payments' => [
                'title' => 'Project Payments',
                'model' => ProjectPayment::class,
                'date_filter' => 'payment_date',
                'tile' => null,
                'perm' => ['add' => 'Add Payment', 'edit' => 'Edit Payment', 'delete' => 'Delete Payment'],
                'order' => 'id',
                'list' => ['invoice_id', 'amount', 'payment_method', 'reference_number', 'status'],
                'fields' => [
                    ['name' => 'invoice_id', 'label' => 'Invoice', 'type' => 'select', 'required' => true, 'source' => ['model' => ProjectInvoice::class, 'value' => 'id', 'label' => 'invoice_number']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'payment_method', 'label' => 'Payment Method', 'type' => 'select', 'required' => true, 'options' => [['value' => 'cash', 'label' => 'Cash'], ['value' => 'bank_transfer', 'label' => 'Bank Transfer'], ['value' => 'cheque', 'label' => 'Cheque'], ['value' => 'mobile_money', 'label' => 'Mobile Money']]],
                    ['name' => 'reference_number', 'label' => 'Reference Number', 'type' => 'text'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => [['value' => 'pending', 'label' => 'Pending'], ['value' => 'completed', 'label' => 'Completed'], ['value' => 'failed', 'label' => 'Failed']]],
                ],
            ],
            'collections' => [
                'title' => 'Collection',
                'model' => Collection::class,
                'tile' => null,
                'perm' => ['add' => 'Add Collection', 'edit' => 'Edit Collection', 'delete' => 'Delete Collection'],
                'order' => 'date',
                'list' => ['date', 'supervisor_id', 'bank_id', 'description', 'amount'],
                'fields' => [
                    ['name' => 'supervisor_id', 'label' => 'Supervisor Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Supervisor::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'bank_id', 'label' => 'Bank Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Bank::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'grosses' => [
                'title' => 'Gross',
                'model' => Gross::class,
                'tile' => null,
                'perm' => ['add' => 'Add Gross Profit', 'edit' => 'Edit Gross Profit', 'delete' => 'Delete Gross Profit'],
                'order' => 'date',
                'list' => ['date', 'supervisor_id', 'description', 'amount'],
                'fields' => [
                    ['name' => 'supervisor_id', 'label' => 'Supervisor Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Supervisor::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                ],
            ],
            'transaction-movements' => [
                'title' => 'Transaction Movement',
                'model' => TransactionMovement::class,
                'tile' => null,
                'perm' => ['add' => 'Add Transaction Movement', 'edit' => 'Edit Transaction Movement', 'delete' => 'Delete Transaction Movement'],
                'order' => 'date',
                'list' => ['date', 'supplier_id', 'description', 'amount'],
                'fields' => [
                    ['name' => 'supplier_id', 'label' => 'Supplier Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Supplier::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                ],
            ],
            'receivings' => [
                'title' => 'Receiving',
                'model' => Receiving::class,
                'tile' => null,
                'perm' => ['add' => 'Add Receiving', 'edit' => 'Edit Receiving', 'delete' => 'Delete Receiving'],
                'order' => 'date',
                'list' => ['date', 'efd_id', 'description', 'amount'],
                'fields' => [
                    ['name' => 'efd_id', 'label' => 'Efd Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Efd::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'supplier-receivings' => [
                'title' => 'Supplier Receiving',
                'model' => SupplierReceiving::class,
                'tile' => null,
                'perm' => ['add' => 'Add Supplier Receiving', 'edit' => 'Edit Supplier Receiving', 'delete' => 'Delete Supplier Receiving'],
                'order' => 'date',
                'list' => ['date', 'supplier_id', 'description', 'amount', 'status'],
                'fields' => [
                    ['name' => 'supplier_id', 'label' => 'Supplier Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Supplier::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                ],
            ],
            'system-inventories' => [
                'title' => 'System Inventory',
                'model' => SystemInventory::class,
                'tile' => null,
                'perm' => ['add' => 'Add System Inventory', 'edit' => 'Edit System Inventory', 'delete' => 'Delete System Inventory'],
                'order' => 'date',
                'list' => ['date', 'system_id', 'amount'],
                'fields' => [
                    ['name' => 'system_id', 'label' => 'System', 'type' => 'select', 'required' => true, 'source' => ['model' => System::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'system-credits' => [
                'title' => 'System Credit',
                'model' => SystemCredit::class,
                'tile' => null,
                'perm' => ['add' => 'Add System Credit', 'edit' => 'Edit System Credit', 'delete' => 'Delete System Credit'],
                'order' => 'date',
                'list' => ['date', 'system_id', 'amount'],
                'fields' => [
                    ['name' => 'system_id', 'label' => 'System', 'type' => 'select', 'required' => true, 'source' => ['model' => System::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'system-capitals' => [
                'title' => 'System Capital',
                'model' => SystemCapital::class,
                'tile' => null,
                'perm' => ['add' => 'Add System Receiving', 'edit' => 'Edit System Receiving', 'delete' => 'Delete System Receiving'],
                'order' => 'date',
                'list' => ['date', 'system_id', 'amount'],
                'fields' => [
                    ['name' => 'system_id', 'label' => 'System', 'type' => 'select', 'required' => true, 'source' => ['model' => System::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'system-cashes' => [
                'title' => 'System Cash',
                'model' => SystemCash::class,
                'tile' => null,
                'perm' => ['add' => 'Add System Cash', 'edit' => 'Edit System Cash', 'delete' => 'Delete System Cash'],
                'order' => 'date',
                'list' => ['date', 'system_id', 'amount'],
                'fields' => [
                    ['name' => 'system_id', 'label' => 'System', 'type' => 'select', 'required' => true, 'source' => ['model' => System::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'withholding-taxes' => [
                'title' => 'Withholding Taxes',
                'model' => WithholdingTax::class,
                'tile' => null,
                'perm' => null,
                'order' => 'date',
                'list' => ['date', 'description', 'amount'],
                'fields' => [
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                ],
            ],
            'adjusted-assessment-taxes' => [
                'title' => 'Adjusted Assessment Taxes',
                'model' => AdjustedAssessmentTax::class,
                'tile' => null,
                'perm' => null,
                'order' => 'date',
                'list' => ['date', 'description', 'amount'],
                'fields' => [
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'file', 'label' => 'Attachment', 'type' => 'file'],
                ],
            ],
            'adjustment-expenses' => [
                'title' => 'Expense Adjustable',
                'model' => AdjustmentExpense::class,
                'tile' => null,
                'perm' => ['add' => 'Add Adjustment Expense', 'edit' => 'Edit Adjustment Expense', 'delete' => 'Delete Adjustment Expense'],
                'order' => 'date',
                'list' => ['date', 'amount'],
                'fields' => [
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'financial-charges' => [
                'title' => 'Financial Charges',
                'model' => FinancialCharge::class,
                'tile' => null,
                'perm' => ['add' => 'Add Financial Charge', 'edit' => 'Edit Financial Charge', 'delete' => 'Delete Financial Charge'],
                'order' => 'date',
                'list' => ['date', 'supplier_id', 'financial_charge_category_id', 'description', 'amount'],
                'fields' => [
                    ['name' => 'supplier_id', 'label' => 'Supplier Name', 'type' => 'select', 'required' => true, 'source' => ['model' => Supplier::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'financial_charge_category_id', 'label' => 'Financial Charge', 'type' => 'select', 'source' => ['model' => FinancialChargeCategory::class, 'value' => 'id', 'label' => 'name']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
