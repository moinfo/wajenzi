<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\AccountType;
use App\Models\AdvanceSalary;
use App\Models\Allowance;
use App\Models\AllowancePayment;
use App\Models\AllowanceSubscription;
use App\Models\Approval;
use App\Models\ApprovalDocumentType;
use App\Models\ApprovalLevel;
use App\Models\Asset;
use App\Models\AssetProperty;
use App\Models\AssignUserGroup;
use App\Models\Bank;
use App\Models\Beneficiary;
use App\Models\Category;
use App\Models\ChartAccount;
use App\Models\ChartAccountUsage;
use App\Models\ChartAccountVariable;
use App\Models\ClientSource;
use App\Models\Deduction;
use App\Models\DeductionSetting;
use App\Models\DeductionSubscription;
use App\Models\Department;
use App\Models\Efd;
use App\Models\ExchangeRate;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\FinancialChargeCategory;
use App\Models\ImprestRequest;
use App\Models\Item;
use App\Models\LeaveType;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\PettyCashRefillRequest;
use App\Models\Position;
use App\Models\ProcessApprovalFlow;
use App\Models\ProcessApprovalFlowStep;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffSalary;
use App\Models\StatutoryPayment;
use App\Models\Stock;
use App\Models\SubCategory;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\System;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UsersPermission;
use App\Models\Wakala;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;



class SettingsController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    public function index(Request $request){
        $settings = [
            ['name'=>'Approval Flows', 'route'=>'hr_settings_process_approval_flows', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Approval Flow Step', 'route'=>'hr_settings_process_approval_flow_steps', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Staff Allowances', 'route'=>'hr_settings_allowances', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Allowance Subscriptions', 'route'=>'allowance_subscriptions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Staff Salary', 'route'=>'hr_settings_staff_salary', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Advance Salary', 'route'=>'hr_settings_advance_salary', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Staff Loan', 'route'=>'hr_settings_staff_loan', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Deductions', 'route'=>'hr_settings_deductions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Deduction Subscriptions', 'route'=>'hr_settings_deduction_subscriptions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Deduction Settings', 'route'=>'hr_settings_deduction_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Departments', 'route'=>'hr_settings_departments', 'icon' => 'si si-settings', 'badge' => 0],
            //['name'=>'Supervisor', 'route'=>'hr_settings_supervisors', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Banks', 'route'=>'hr_settings_banks', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Assets', 'route'=>'hr_settings_assets', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Asset Properties', 'route'=>'hr_settings_asset_properties', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Systems', 'route'=>'hr_settings_systems', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Users', 'route'=>'hr_settings_users', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Positions', 'route'=>'hr_settings_positions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Roles', 'route'=>'hr_settings_roles', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Permissions', 'route'=>'hr_settings_permissions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Suppliers', 'route'=>'hr_settings_suppliers', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Items', 'route'=>'hr_settings_items', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Expenses Categories', 'route'=>'hr_settings_expenses_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Expenses Sub Categories', 'route'=>'hr_settings_expenses_sub_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Financial Charge Categories', 'route'=>'hr_settings_financial_charge_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'EFD', 'route'=>'hr_settings_efd', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Approval Document Type', 'route'=>'hr_settings_approval_document_types', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Approval', 'route'=>'hr_settings_approvals', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Approval Level', 'route'=>'hr_settings_approval_levels', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'User Group', 'route'=>'hr_settings_user_groups', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Assign User Group', 'route'=>'hr_settings_assign_user_groups', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Statutory Payment', 'route'=>'hr_settings_statutory_payments', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Statutory Payment Category', 'route'=>'hr_settings_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Statutory Payment Sub Category', 'route'=>'hr_settings_sub_categories', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'System Settings', 'route'=>'system_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Client Sources', 'route'=>'client_sources', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Beneficiaries', 'route'=>'beneficiaries', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Mawakala', 'route'=>'wakalas', 'icon' => 'si si-settings', 'badge' => 0],
        ];
        $data = [
            'settings' => $settings
        ];

        return view('pages.settings.settings_index')->with($data);
    }

    public function beneficiaries(Request $request)
    {
        if($this->handleCrud($request, 'Beneficiary')) {
            return back();
        }

        $data = [
            'beneficiaries' => Beneficiary::with('accounts')->get()
        ];

        return view('pages.beneficiary.beneficiary_index')->with($data);
    }

    public function beneficiary_account(Request $request)
    {
        if($this->handleCrud($request, 'BeneficiaryAccount')) {
            return back();
        }

       Redirect::back();
    }

    public function wakalas(Request $request)
    {
        if($this->handleCrud($request, 'Wakala')) {
            return back();
        }

        $data = [
            'wakalas' => Wakala::all()
        ];
        return view('pages.wakala.wakala_index')->with($data);
    }
    public function stock(Request $request){
        if($this->handleCrud($request, 'Stock')) {
            return back();
        }
        $data = [
            'stocks' => Stock::all()
        ];
        return view('pages.settings.settings_stocks')->with($data);
    }

    public function allowances(Request $request){
        if($this->handleCrud($request, 'Allowance')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-01-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $allowance_payments = AllowancePayment::whereBetween('date',[$start_date,$end_date])->get();

        $data = [
            'allowances' => Allowance::all(),
            'allowance_payments' => $allowance_payments,
            'staffs' => User::where('users.status','ACTIVE')->with('allowance_subscriptions.allowance')->get(),
            'deductions' => Deduction::all(),
            'deduction_settings' => DeductionSetting::all(),
            'only_staffs' => Staff::onlyStaffsWithBreakfastOrLunch(),
//            'deduction_subscriptions' => Staff::with('deductionSubscriptions')->get(),
            'deduction_subscriptions' => DeductionSubscription::all(),
        ];
        return view('pages.settings.settings_allowances')->with($data);
    }


    public function makeReadNotification(Request $request){
        $notification_id = $request->id;
        $link = $request->link;
        $id = $request->id;
        DB::table('notifications')->where('id',$notification_id)->update(['read_at'=>Carbon::now()]);
        redirect()->route('hr_settings_statutory_payment', ['id' => $id]);
    }


    public function allowance_subscriptions(Request $request){
        if($this->handleCrud($request, 'AllowanceSubscription')) {
            return back();
        }
        $data = [
            'allowance_subscriptions' => AllowanceSubscription::all()
        ];
        return view('pages.settings.settings_allowance_subscriptions')->with($data);
    }
    public function process_approval_flows(Request $request){
        if($this->handleCrud($request, 'ProcessApprovalFlow')) {
            return back();
        }
        $data = [
            'process_approval_flows' => ProcessApprovalFlow::all()
        ];
        return view('pages.settings.settings_process_approval_flow')->with($data);
    }
    public function process_approval_flow_steps(Request $request){
        if($this->handleCrud($request, 'ProcessApprovalFlowStep')) {
            return back();
        }
        $data = [
            'process_approval_flow_steps' => ProcessApprovalFlowStep::all()
        ];
        return view('pages.settings.settings_process_approval_flow_step')->with($data);
    }

    public function system_settings(Request $request){
        if($this->handleCrud($request, 'SystemSetting')) {
            return back();
        }
        $data = [
            'system_settings' => SystemSetting::all()
        ];
        return view('pages.settings.settings_system_settings')->with($data);
    }

    public function leave_types(Request $request){
        if($this->handleCrud($request, 'LeaveType')) {
            return back();
        }
        $data = [
            'leave_types' => LeaveType::all()
        ];
        return view('pages.settings.settings_leave_types')->with($data);
    }

    public function client_sources(Request $request){
        if($this->handleCrud($request, 'ClientSource')) {
            return back();
        }
        $data = [
            'client_sources' => ClientSource::all()
        ];
        return view('pages.settings.settings_client_sources')->with($data);
    }


    public function deduction_subscriptions(Request $request){
        if($this->handleCrud($request, 'DeductionSubscription')) {
            return back();
        }
        $data = [
            'deduction_subscriptions' => DeductionSubscription::all()
        ];
        return view('pages.settings.settings_deduction_subscriptions')->with($data);
    }


    public function deduction_settings(Request $request){
        if($this->handleCrud($request, 'DeductionSetting')) {
            return back();
        }
        $data = [
            'deduction_settings' => DeductionSetting::all()
        ];
        return view('pages.settings.settings_deduction_settings')->with($data);
    }


    public function deductions(Request $request){
        if($this->handleCrud($request, 'Deduction')) {
            return back();
        }
        $data = [
            'deductions' => Deduction::all()
        ];
        return view('pages.settings.settings_deductions')->with($data);
    }


    public function banks(Request $request){
        if($this->handleCrud($request, 'Bank')) {
            return back();
        }
        $data = [
            'banks' => Bank::all()
        ];
        return view('pages.settings.settings_banks')->with($data);
    }

    public function account_types(Request $request){
        if($this->handleCrud($request, 'AccountType')) {
            return back();
        }
        $data = [
            'account_types' => AccountType::all()
        ];
        return view('pages.settings.settings_account_types')->with($data);
    }
    public function petty_cash_refill_requests(Request $request){
        if($this->handleCrud($request, 'PettyCashRefillRequest')) {
            return back();
        }
        $data = [
            'petty_cash_refill_requests' => PettyCashRefillRequest::all()
        ];
        return view('pages.petty_cash_management.petty_cash_refill_requests')->with($data);
    }

    public function petty_cash_refill_request($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'petty_cash_refill_requests');

        $approval_data = \App\Models\PettyCashRefillRequest::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Balance' => number_format($approval_data->balance),
            'Refill Amount' => number_format($approval_data->refill_amount),
            'Document Number' => $approval_data->document_number,
            'Date' => $approval_data->date,
//            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Petty Cash Refill Request',
            'approval_data_name' => $approval_data->user->name,
            'details' => $details,
            'model' => 'PettyCashRefillRequest',
            'route' => 'petty_cash_refill_requests',

        ];
        return view('approvals._approve_page')->with($data);
    }

    public function imprest_requests(Request $request){
        if($this->handleCrud($request, 'ImprestRequest')) {
            return back();
        }
        $data = [
            'imprest_requests' => ImprestRequest::all()
        ];
        return view('pages.imprest.imprest_requests')->with($data);
    }

    public function imprest_request($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'imprest_requests');

        $approval_data = \App\Models\ImprestRequest::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Document Number' => $approval_data->document_number,
            'Description' => $approval_data->description,
            'Amount' => number_format($approval_data->amount),
            'Expenses Category' => $approval_data->expenseSubCategory->expensesCategory->name ?? 'N/A',
            'Expenses Sub Category' => $approval_data->expenseSubCategory->name ?? 'N/A',
            'Project Name' => $approval_data->project->project_name ?? 'N/A',
            'Uploaded File' => $approval_data->file,
            'Date' => $approval_data->date,
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Imprest Request',
            'approval_data_name' => $approval_data->user->name ?? 'N/A',
            'details' => $details,
            'model' => 'ImprestRequest',
            'route' => 'imprest_requests',

        ];
        return view('approvals._approve_page')->with($data);
    }

    public function chart_of_account_variables(Request $request){
        if($this->handleCrud($request, 'ChartAccountVariable')) {
            return back();
        }
        $data = [
            'chart_account_variables' => ChartAccountVariable::all()
        ];
        return view('pages.settings.settings_chart_account_variables')->with($data);
    }

    public function charts_of_account_usages(Request $request){
        if($this->handleCrud($request, 'ChartAccountUsage')) {
            return back();
        }
        $data = [
            'charts_account_usages' => ChartAccountUsage::all()
        ];
        return view('pages.settings.settings_charts_account_usages')->with($data);
    }

    public function charts_of_accounts(Request $request)
    {
        // Handle CRUD operations if any
        if ($this->handleCrud($request, 'ChartAccount')) {
            return back();
        }

        // If this is an AJAX call to save a new chart account
        if ($request->ajax() && $request->isMethod('post')) {
            try {
                $data = $request->all();

                // Ensure parent field is properly handled
                if (empty($data['parent'])) {
                    $data['parent'] = null;
                }

                if (!empty($data['id'])) {
                    // Update existing
                    $chartAccount = ChartAccount::findOrFail($data['id']);
                    $chartAccount->update($data);
                    $message = 'Chart account updated successfully';
                } else {
                    // Create new
                    ChartAccount::create($data);
                    $message = 'Chart account created successfully';
                }

                return response()->json(['success' => true, 'message' => $message]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        // Load data for view
        $data = [
            'chart_of_accounts' => ChartAccount::with(['accountType', 'parentAccount'])->get(),
            'account_types' => AccountType::all(),
        ];

        return view('pages.settings.settings_charts_of_accounts')->with($data);
    }

    /**
     * API endpoint to get chart accounts by account type.
     *
     * @param Request $request
     * @param int $accountTypeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartAccountsByType(Request $request, $accountTypeId)
    {
        $excludeId = $request->input('exclude_id');
        $query = ChartAccount::where('account_type', $accountTypeId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $accounts = $query->get();

        return response()->json($accounts);
    }
    public function exchange_rates(Request $request){
        if($this->handleCrud($request, 'ExchangeRate')) {
            return back();
        }
        $data = [
            'exchange_rates' => ExchangeRate::all()
        ];
        return view('pages.settings.settings_exchange_rates')->with($data);
    }

    public function assets(Request $request){
        if($this->handleCrud($request, 'Asset')) {
            return back();
        }
        $data = [
            'assets' => Asset::all()
        ];
        return view('pages.settings.settings_assets')->with($data);
    }

    public function asset_properties(Request $request){
        if($this->handleCrud($request, 'AssetProperty')) {
            return back();
        }
        $data = [
            'asset_properties' => AssetProperty::all()
        ];
        return view('pages.settings.settings_asset_properties')->with($data);
    }


    public function staff_salaries(Request $request){
        if($this->handleCrud($request, 'StaffSalary')) {
            return back();
        }
        $data = [
            'staff_salaries' => StaffSalary::all()
        ];
        return view('pages.settings.settings_staff_salaries')->with($data);
    }


    public function staff_loans(Request $request){
        if($this->handleCrud($request, 'Loan')) {
            return back();
        }
        $data = [
            'staff_loans' => Loan::all()
        ];
        return view('pages.settings.settings_loans')->with($data);
    }


    public function advance_salaries(Request $request){
        if($this->handleCrud($request, 'AdvanceSalary')) {
            return back();
        }
        $data = [
            'advance_salaries' => AdvanceSalary::all()
        ];
        return view('pages.settings.settings_advance_salaries')->with($data);
    }


    public function systems(Request $request){
        if($this->handleCrud($request, 'System')) {
            return back();
        }
        $data = [
            'systems' => System::all()
        ];
        return view('pages.settings.settings_systems')->with($data);
    }


    public function users(Request $request){
        if($request->permission_id){
            DB::table('users_permissions')->where('user_id', '=', $request->user_id)->delete();
            foreach($request->permission_id as $permissions) {
                UsersPermission::create([
                    'user_id' => $request->user_id,
                    'permission_id' => $permissions,
                ]);
            }
        }

        if($this->handleCrud($request, 'User')) {
            return back();
        }
        $data = [
            'users' => User::all()
        ];
        return view('pages.settings.settings_users')->with($data);
    }


    public function supervisors(Request $request){
        if($this->handleCrud($request, 'Supervisor')) {
            return back();
        }
        $data = [
            'supervisors' => Supervisor::all()
        ];
        return view('pages.settings.settings_supervisors')->with($data);
    }


    public function departments(Request $request){
        if($this->handleCrud($request, 'Department')) {
            return back();
        }
        $data = [
            'departments' => Department::all()
        ];
        return view('pages.settings.settings_departments')->with($data);
    }


    public function positions(Request $request){
        if($this->handleCrud($request, 'Position')) {
            return back();
        }
        $data = [
            'positions' => Position::all()
        ];
        return view('pages.settings.settings_positions')->with($data);
    }


    public function roles(Request $request){
        if($this->handleCrud($request, 'Role')) {
            return back();
        }
        $data = [
            'roles' => Role::all()
        ];
        return view('pages.settings.settings_roles')->with($data);
    }


    public function permissions(Request $request){
        if($this->handleCrud($request, 'Permission')) {
            return back();
        }
        $data = [
            'permissions' => Permission::all()
        ];
        return view('pages.settings.settings_permissions')->with($data);
    }


    public function suppliers(Request $request){
        if($this->handleCrud($request, $request->addItem)) {
            return back();
        }
        $data = [
            'suppliers' => Supplier::all()
        ];
        return view('pages.settings.settings_suppliers')->with($data);
    }


    public function items(Request $request){
        if($this->handleCrud($request, 'Item')) {
            return back();
        }
        $data = [
            'items' => Item::all()
        ];
        return view('pages.settings.settings_items')->with($data);
    }


    public function expenses_categories(Request $request){
        if($this->handleCrud($request, 'ExpensesCategory')) {
            return back();
        }
        $data = [
            'expenses_categories' => ExpensesCategory::all()
        ];
        return view('pages.settings.settings_expenses_categories')->with($data);
    }


    public function expenses_sub_categories(Request $request){
        if($this->handleCrud($request, 'ExpensesSubCategory')) {
            return back();
        }
        $data = [
            'expenses_sub_categories' => ExpensesSubCategory::all()
        ];
        return view('pages.settings.settings_expenses_sub_categories')->with($data);
    }


    public function financial_charge_categories(Request $request){
        if($this->handleCrud($request, 'FinancialChargeCategory')) {
            return back();
        }
        $data = [
            'financial_charge_categories' => FinancialChargeCategory::all()
        ];
        return view('pages.settings.settings_financial_charge_categories')->with($data);
    }


    public function efd(Request $request){
        if($this->handleCrud($request, 'Efd')) {
            return back();
        }
        $data = [
            'efd' => Efd::all()
        ];
        return view('pages.settings.settings_efds')->with($data);
    }

    public function approval_document_types(Request $request){
        if($this->handleCrud($request, 'ApprovalDocumentType')) {
            return back();
        }
        $data = [
            'approval_document_types' => ApprovalDocumentType::all()
        ];
        return view('pages.settings.settings_approval_document_types')->with($data);
    }


    public function approvals(Request $request){
        if($this->handleCrud($request, 'Approval')) {
            return back();
        }
        $data = [
            'approvals' => Approval::all()
        ];
        return view('pages.settings.settings_approvals')->with($data);
    }


    public function advance_salary($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'advance_salaries');

        $approval_data = \App\Models\AdvanceSalary::where('id',$id)->get()->first();

        $document_id = $id;

        $details = [
            'Total Amount' => number_format($approval_data->amount),
            'Description' => $approval_data->description,
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Advance Salaries',
            'approval_data_name' => $approval_data->staff->name,
            'details' => $details,
            'model' => 'AdvanceSalary',
            'route' => 'advance_salaries',

        ];
        return view('approvals._approve_page')->with($data);
    }

    public function staff_loan($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'staff_loans');

        $approval_data = \App\Models\Loan::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Total Amount' => number_format($approval_data->amount),
            'Description' => $approval_data->description,
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Staff Loan',
            'approval_data_name' => $approval_data->staff->name,
            'details' => $details,
            'model' => 'Loan',
            'route' => 'staff_loans',

        ];
        return view('approvals._approve_page')->with($data);
    }




    public function sub_categories(Request $request){
        if($this->handleCrud($request, 'SubCategory')) {
            return back();
        }
        $data = [
            'sub_categories' => SubCategory::all()
        ];
        return view('pages.settings.settings_sub_categories')->with($data);
    }


    public function categories(Request $request){
        if($this->handleCrud($request, 'Category')) {
            return back();
        }
        $data = [
            'categories' => Category::all()
        ];
        return view('pages.settings.settings_categories')->with($data);
    }


    public function user_groups(Request $request){
        if($this->handleCrud($request, 'UserGroup')) {
            return back();
        }
        $data = [
            'user_groups' => UserGroup::all()
        ];
        return view('pages.settings.settings_user_groups')->with($data);
    }


    public function assign_user_groups(Request $request){
        if($this->handleCrud($request, 'AssignUserGroup')) {
            return back();
        }
        $data = [
            'assign_user_groups' => AssignUserGroup::all()
        ];
        return view('pages.settings.settings_assign_user_groups')->with($data);
    }


    public function approval_levels(Request $request){
        if($this->handleCrud($request, 'ApprovalLevel')) {
            return back();
        }
        $data = [
            'approval_levels' => ApprovalLevel::all()
        ];
        return view('pages.settings.settings_approval_levels')->with($data);
    }

    public function updateRolePermissions(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'nullable|array',
            'permission_id.*' => 'exists:permissions,id',
        ]);

        try {
            // Find the role using direct DB query to avoid model errors
            $role = DB::table('roles')->where('id', $request->role_id)->first();

            if (!$role) {
                return redirect()->back()->with('error', "Role not found!");
            }

            // If updating permissions
            if ($request->has('update_permissions')) {
                // Clear existing permissions for this role
                DB::table('role_has_permissions')->where('role_id', $request->role_id)->delete();

                // Add new permissions
                if ($request->has('permission_id') && is_array($request->permission_id)) {
                    $permissionsToAdd = [];
                    foreach ($request->permission_id as $permissionId) {
                        $permissionsToAdd[] = [
                            'permission_id' => $permissionId,
                            'role_id' => $request->role_id
                        ];
                    }

                    if (!empty($permissionsToAdd)) {
                        DB::table('role_has_permissions')->insert($permissionsToAdd);
                    }
                }

                return redirect()->back()->with('success', "Permissions for role '{$role->name}' updated successfully!");
            }

            // If just selecting a role, redirect back to show the permissions
            return redirect()->route('hr_settings_users', ['role_id' => $request->role_id]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Error updating permissions: " . $e->getMessage());
        }
    }

    /**
     * Assign users to a role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignUsersToRole(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'user_id' => 'nullable|array',
            'user_id.*' => 'exists:users,id',
        ]);

        try {
            // Find the role
            $role = DB::table('roles')->where('id', $request->role_id)->first();

            if (!$role) {
                return redirect()->back()->with('error', "Role not found!");
            }

            // If just selecting a role (no update flag), redirect back to show users
            if (!$request->has('update_role_users')) {
                return redirect()->route('hr_settings_users', ['role_id' => $request->role_id]);
            }

            // Remove all users from this role
            DB::table('model_has_roles')
                ->where('role_id', $request->role_id)
                ->where('model_type', 'App\\Models\\User')  // Adjust if your user model is different
                ->delete();

            // Add the selected users to this role
            if ($request->has('user_id') && is_array($request->user_id)) {
                $toAdd = [];
                foreach ($request->user_id as $userId) {
                    $toAdd[] = [
                        'role_id' => $request->role_id,
                        'model_id' => $userId,
                        'model_type' => 'App\\Models\\User'  // Adjust if your user model is different
                    ];
                }

                if (!empty($toAdd)) {
                    DB::table('model_has_roles')->insert($toAdd);
                }
            }

            return redirect()->back()->with('success', "Users for role '{$role->name}' updated successfully!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Error updating users for role: " . $e->getMessage());
        }
    }

}
