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
use App\Models\ServiceInterested;
use App\Models\LeadStatus;
use App\Models\LeadSource;
use App\Models\ProjectType;
use App\Models\ServiceType;
use App\Models\ProjectStatus;
use App\Models\CostCategory;
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
use App\Traits\ClearsPermissionCache;
use Spatie\Permission\PermissionRegistrar;

// BOQ Template Models
use App\Models\BuildingType;
use App\Models\BoqItemCategory;
use App\Models\ConstructionStage;
use App\Models\Activity;
use App\Models\SubActivity;
use App\Models\BoqTemplateItem;
use App\Models\SubActivityMaterial;
use App\Models\BoqTemplate;
use App\Models\BoqTemplateStage;
use App\Models\BoqTemplateActivity;
use App\Models\BoqTemplateSubActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;



class SettingsController extends Controller
{
    use ClearsPermissionCache;
    
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
            ['name'=>'Service Interesteds', 'route'=>'hr_settings_service_interesteds', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Lead Statuses', 'route'=>'hr_settings_lead_statuses', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Lead Sources', 'route'=>'hr_settings_lead_sources', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Project Types', 'route'=>'hr_settings_project_types_settings', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Service Types', 'route'=>'hr_settings_service_types', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Project Statuses', 'route'=>'hr_settings_project_statuses', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Cost Categories', 'route'=>'hr_settings_cost_categories', 'icon' => 'si si-settings', 'badge' => 0],
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
            
            // BOQ Template System
            ['name'=>'Building Types', 'route'=>'hr_settings_building_types', 'icon' => 'si si-home', 'badge' => 0],
            ['name'=>'BOQ Item Categories', 'route'=>'hr_settings_boq_item_categories', 'icon' => 'si si-list', 'badge' => 0],
            ['name'=>'Construction Stages', 'route'=>'hr_settings_construction_stages', 'icon' => 'si si-layers', 'badge' => 0],
            ['name'=>'Activities', 'route'=>'hr_settings_activities', 'icon' => 'si si-wrench', 'badge' => 0],
            ['name'=>'Sub-Activities', 'route'=>'hr_settings_sub_activities', 'icon' => 'si si-puzzle', 'badge' => 0],
            ['name'=>'BOQ Items', 'route'=>'hr_settings_boq_items', 'icon' => 'si si-bag', 'badge' => 0],
            ['name'=>'BOQ Templates', 'route'=>'hr_settings_boq_templates', 'icon' => 'si si-docs', 'badge' => 0],
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

    public function service_interesteds(Request $request){
        if($this->handleCrud($request, 'ServiceInterested')) {
            return back();
        }
        $data = [
            'service_interesteds' => ServiceInterested::all()
        ];
        return view('pages.settings.settings_service_interesteds')->with($data);
    }

    public function lead_statuses(Request $request){
        if($this->handleCrud($request, 'LeadStatus')) {
            return back();
        }
        $data = [
            'lead_statuses' => LeadStatus::all()
        ];
        return view('pages.settings.settings_lead_statuses')->with($data);
    }

    public function lead_sources(Request $request){
        if($this->handleCrud($request, 'LeadSource')) {
            return back();
        }
        $data = [
            'objects' => LeadSource::all()
        ];
        return view('pages.settings.settings_lead_sources')->with($data);
    }

    public function project_types_settings(Request $request){
        if($this->handleCrud($request, 'ProjectType')) {
            return back();
        }
        $data = [
            'objects' => ProjectType::all()
        ];
        return view('pages.settings.settings_project_types_settings')->with($data);
    }

    public function service_types(Request $request){
        if($this->handleCrud($request, 'ServiceType')) {
            return back();
        }
        $data = [
            'objects' => ServiceType::all()
        ];
        return view('pages.settings.settings_service_types')->with($data);
    }

    public function project_statuses(Request $request){
        if($this->handleCrud($request, 'ProjectStatus')) {
            return back();
        }
        $data = [
            'objects' => ProjectStatus::all()
        ];
        return view('pages.settings.settings_project_statuses')->with($data);
    }

    public function cost_categories(Request $request){
        if($this->handleCrud($request, 'CostCategory')) {
            return back();
        }
        $data = [
            'objects' => CostCategory::all()
        ];
        return view('pages.settings.settings_cost_categories')->with($data);
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
            // Clear permission cache after assigning permissions
            $this->clearPermissionCache();
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
            // Debug logging
            \Log::info('Role permissions update request', [
                'role_id' => $request->role_id,
                'permission_id' => $request->permission_id,
                'update_permissions' => $request->has('update_permissions'),
                'all_input' => $request->all()
            ]);

            // Find the role using Eloquent model
            $role = Role::find($request->role_id);

            if (!$role) {
                return redirect()->back()->with('error', "Role not found!");
            }

            // If updating permissions
            if ($request->has('update_permissions')) {
                // Use Eloquent to properly handle cache invalidation
                $permissionIds = $request->permission_id ?? [];
                
                // Simple debug - dump data and die to see what's being sent
                if (empty($permissionIds)) {
                    return redirect()->back()->with('error', "No permissions selected. Please select at least one permission.");
                }
                
                $role->permissions()->sync($permissionIds);

                // Clear permission cache after updating role permissions
                $this->clearPermissionCache();

                return redirect()->back()->with('success', "Permissions for role '{$role->name}' updated successfully! Updated " . count($permissionIds) . " permissions.");
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

            // Clear permission cache after assigning users to roles
            $this->clearPermissionCache();

            return redirect()->back()->with('success', "Users for role '{$role->name}' updated successfully!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Error updating users for role: " . $e->getMessage());
        }
    }

    // BOQ Template System Methods

    public function building_types(Request $request)
    {
        if($this->handleCrud($request, 'BuildingType')) {
            return back();
        }
        
        $data = [
            'building_types' => BuildingType::with('parent')->orderBy('sort_order')->get(),
            'parent_building_types' => BuildingType::whereNull('parent_id')->orderBy('name')->get()
        ];
        
        return view('pages.settings.settings_building_types')->with($data);
    }

    public function boq_item_categories(Request $request)
    {
        if($this->handleCrud($request, 'BoqItemCategory')) {
            return back();
        }
        
        $data = [
            'categories' => BoqItemCategory::with('parent')->orderBy('sort_order')->get(),
            'parent_categories' => BoqItemCategory::whereNull('parent_id')->orderBy('name')->get()
        ];
        
        return view('pages.settings.settings_boq_item_categories')->with($data);
    }

    public function construction_stages(Request $request)
    {
        if($this->handleCrud($request, 'ConstructionStage')) {
            return back();
        }
        
        $data = [
            'construction_stages' => ConstructionStage::with('parent')->orderBy('sort_order')->get(),
            'parent_construction_stages' => ConstructionStage::whereNull('parent_id')->orderBy('name')->get()
        ];
        
        return view('pages.settings.settings_construction_stages')->with($data);
    }

    public function activities(Request $request)
    {
        if($this->handleCrud($request, 'Activity')) {
            return back();
        }
        
        $data = [
            'activities' => Activity::with('constructionStage')->orderBy('sort_order')->get(),
            'construction_stages' => ConstructionStage::orderBy('sort_order')->get()
        ];
        
        return view('pages.settings.settings_activities')->with($data);
    }

    public function sub_activities(Request $request)
    {
        if($this->handleCrud($request, 'SubActivity')) {
            return back();
        }
        
        $data = [
            'sub_activities' => SubActivity::with('activity.constructionStage')->orderBy('sort_order')->get(),
            'activities' => Activity::with(['constructionStage', 'subActivities'])->orderBy('sort_order')->get(),
            'skill_levels' => [
                ['name' => 'unskilled'], 
                ['name' => 'semi_skilled'], 
                ['name' => 'skilled'], 
                ['name' => 'specialist']
            ],
            'duration_units' => [
                ['name' => 'hours'], 
                ['name' => 'days'], 
                ['name' => 'weeks']
            ]
        ];
        
        return view('pages.settings.settings_sub_activities')->with($data);
    }

    public function boq_items(Request $request)
    {
        if($this->handleCrud($request, 'BoqTemplateItem')) {
            return back();
        }
        
        $data = [
            'boq_items' => BoqTemplateItem::with('category')->get(),
            'categories' => BoqItemCategory::with('boqItems')->orderBy('sort_order')->get()
        ];
        
        return view('pages.settings.settings_boq_items')->with($data);
    }

    public function boq_templates(Request $request)
    {
        if($this->handleCrud($request, 'BoqTemplate')) {
            return back();
        }
        
        $data = [
            'boq_templates' => BoqTemplate::with(['buildingType.parent', 'creator'])->get(),
            'building_types' => BuildingType::with('parent')->where('is_active', true)->orderBy('name')->get(),
            'construction_stages' => ConstructionStage::orderBy('sort_order')->get()
        ];
        
        return view('pages.settings.settings_boq_templates')->with($data);
    }

    /**
     * Load BOQ Template Builder
     */
    public function boq_template_builder(Request $request)
    {
        // Debug logging to diagnose template not found issue
        \Log::info('BOQ Template Builder Access Debug', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'templateId_param' => $request->input('templateId'),
            'template_id_param' => $request->input('template_id'),
            'all_params' => $request->all(),
            'user_id' => auth()->id()
        ]);
        
        $templateId = $request->input('templateId') ?? $request->input('template_id');
        
        // Handle malformed URL parameters
        if (!$templateId) {
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'templateId') !== false) {
                    $templateId = $value;
                    break;
                }
            }
        }
        $template = null;
        $selectedStages = [];
        $templateStats = ['stages' => 0, 'activities' => 0, 'subActivities' => 0];
        
        // Additional debug logging for template lookup
        \Log::info('BOQ Template Builder Template Lookup', [
            'final_template_id' => $templateId,
            'template_id_type' => gettype($templateId)
        ]);
        
        // Handle POST requests for actions
        if ($request->isMethod('post')) {
            $action = $request->input('action');
            $templateId = $request->input('template_id');
            
            \Log::info('BOQ Template Builder Action', [
                'action' => $action,
                'template_id' => $templateId,
                'all_inputs' => $request->all()
            ]);
            
            if ($templateId && $action) {
                $template = BoqTemplate::find($templateId);
                
                if ($template) {
                    switch ($action) {
                        case 'add_stage':
                            $stageId = $request->input('stage_id');
                            $selectedChildren = $request->input('selected_children', []);
                            
                            if ($stageId) {
                                $addedStages = [];
                                $existingStages = [];
                                $maxSortOrder = \App\Models\BoqTemplateStage::where('boq_template_id', $templateId)->max('sort_order') ?? 0;
                                
                                // Add parent stage
                                $parentExists = \App\Models\BoqTemplateStage::where('boq_template_id', $templateId)
                                    ->where('construction_stage_id', $stageId)
                                    ->exists();
                                    
                                if (!$parentExists) {
                                    $templateStage = new \App\Models\BoqTemplateStage();
                                    $templateStage->boq_template_id = $templateId;
                                    $templateStage->construction_stage_id = $stageId;
                                    $templateStage->sort_order = ++$maxSortOrder;
                                    $templateStage->save();
                                    $addedStages[] = ConstructionStage::find($stageId)->name ?? "Stage ID: $stageId";
                                } else {
                                    $existingStages[] = ConstructionStage::find($stageId)->name ?? "Stage ID: $stageId";
                                }
                                
                                // Add selected children stages
                                foreach ($selectedChildren as $childStageId) {
                                    $childExists = \App\Models\BoqTemplateStage::where('boq_template_id', $templateId)
                                        ->where('construction_stage_id', $childStageId)
                                        ->exists();
                                        
                                    if (!$childExists) {
                                        $childTemplateStage = new \App\Models\BoqTemplateStage();
                                        $childTemplateStage->boq_template_id = $templateId;
                                        $childTemplateStage->construction_stage_id = $childStageId;
                                        $childTemplateStage->sort_order = ++$maxSortOrder;
                                        $childTemplateStage->save();
                                        $addedStages[] = ConstructionStage::find($childStageId)->name ?? "Child Stage ID: $childStageId";
                                    } else {
                                        $existingStages[] = ConstructionStage::find($childStageId)->name ?? "Child Stage ID: $childStageId";
                                    }
                                }
                                
                                // Provide feedback
                                if (!empty($addedStages)) {
                                    session()->flash('success', 'Added stages: ' . implode(', ', $addedStages));
                                }
                                if (!empty($existingStages)) {
                                    session()->flash('warning', 'These stages were already in the template: ' . implode(', ', $existingStages));
                                }
                                if (empty($addedStages) && empty($existingStages)) {
                                    session()->flash('warning', 'No stages were added. Please select a parent stage and optionally children.');
                                }
                            }
                            break;
                            
                        case 'add_activity':
                            $stageId = $request->input('parent_stage_id');
                            $activityName = $request->input('activity_name');
                            
                            if ($stageId && $activityName) {
                                // Get the template stage to find the construction stage
                                $templateStage = \App\Models\BoqTemplateStage::find($stageId);
                                
                                if ($templateStage) {
                                    // Find or create an Activity for this construction stage
                                    $activity = \App\Models\Activity::firstOrCreate([
                                        'construction_stage_id' => $templateStage->construction_stage_id,
                                        'name' => $activityName
                                    ], [
                                        'description' => "Activity for {$activityName}",
                                        'sort_order' => \App\Models\Activity::where('construction_stage_id', $templateStage->construction_stage_id)->max('sort_order') + 1 ?? 1
                                    ]);
                                    
                                    // Check if this activity is already added to the template stage
                                    $exists = \App\Models\BoqTemplateActivity::where('boq_template_stage_id', $stageId)
                                        ->where('activity_id', $activity->id)
                                        ->exists();
                                        
                                    if (!$exists) {
                                        // Create the template activity reference
                                        $templateActivity = new \App\Models\BoqTemplateActivity();
                                        $templateActivity->boq_template_stage_id = $stageId;
                                        $templateActivity->activity_id = $activity->id;
                                        $templateActivity->sort_order = \App\Models\BoqTemplateActivity::where('boq_template_stage_id', $stageId)->max('sort_order') + 1 ?? 1;
                                        $templateActivity->save();
                                        
                                        session()->flash('success', 'Activity added successfully.');
                                    } else {
                                        session()->flash('warning', 'This activity is already added to the stage.');
                                    }
                                } else {
                                    session()->flash('error', 'Template stage not found.');
                                }
                            }
                            break;
                            
                        case 'add_sub_activity':
                            $templateActivityId = $request->input('parent_activity_id');
                            $subActivityName = $request->input('sub_activity_name');
                            
                            if ($templateActivityId && $subActivityName) {
                                // Get the template activity to find the actual activity
                                $templateActivity = \App\Models\BoqTemplateActivity::with('activity')->find($templateActivityId);
                                
                                if ($templateActivity && $templateActivity->activity) {
                                    // Find or create a SubActivity for this activity
                                    $subActivity = \App\Models\SubActivity::firstOrCreate([
                                        'activity_id' => $templateActivity->activity->id,
                                        'name' => $subActivityName
                                    ], [
                                        'description' => "Sub-activity for {$subActivityName}",
                                        'estimated_duration_hours' => 8,
                                        'duration_unit' => 'hours',
                                        'labor_requirement' => 1,
                                        'skill_level' => 'Basic',
                                        'can_run_parallel' => false,
                                        'weather_dependent' => false,
                                        'sort_order' => \App\Models\SubActivity::where('activity_id', $templateActivity->activity->id)->max('sort_order') + 1 ?? 1
                                    ]);
                                    
                                    // Check if this sub-activity is already added to the template activity
                                    $exists = \App\Models\BoqTemplateSubActivity::where('boq_template_activity_id', $templateActivityId)
                                        ->where('sub_activity_id', $subActivity->id)
                                        ->exists();
                                        
                                    if (!$exists) {
                                        // Create the template sub-activity reference
                                        $templateSubActivity = new \App\Models\BoqTemplateSubActivity();
                                        $templateSubActivity->boq_template_activity_id = $templateActivityId;
                                        $templateSubActivity->sub_activity_id = $subActivity->id;
                                        $templateSubActivity->sort_order = \App\Models\BoqTemplateSubActivity::where('boq_template_activity_id', $templateActivityId)->max('sort_order') + 1 ?? 1;
                                        $templateSubActivity->save();
                                        
                                        session()->flash('success', 'Sub-activity added successfully.');
                                    } else {
                                        session()->flash('warning', 'This sub-activity is already added to the activity.');
                                    }
                                } else {
                                    session()->flash('error', 'Template activity not found.');
                                }
                            }
                            break;
                            
                        case 'assign_materials':
                            $subActivityId = $request->input('sub_activity_id');
                            $boqItemId = $request->input('boq_item_id');
                            $quantity = $request->input('quantity');
                            
                            if ($subActivityId && $boqItemId && $quantity) {
                                // Check if this material assignment already exists
                                $exists = \App\Models\SubActivityMaterial::where('sub_activity_id', $subActivityId)
                                    ->where('boq_item_id', $boqItemId)
                                    ->exists();
                                    
                                if (!$exists) {
                                    // Create the material assignment
                                    $materialAssignment = new \App\Models\SubActivityMaterial();
                                    $materialAssignment->sub_activity_id = $subActivityId;
                                    $materialAssignment->boq_item_id = $boqItemId;
                                    $materialAssignment->quantity = $quantity;
                                    $materialAssignment->save();
                                    
                                    session()->flash('success', 'Material assigned to sub-activity successfully.');
                                } else {
                                    session()->flash('warning', 'This material is already assigned to the sub-activity.');
                                }
                            } else {
                                session()->flash('error', 'Please fill in all required fields.');
                            }
                            break;
                            
                        case 'remove_stage':
                            $stageId = $request->input('stage_id_to_remove');
                            if ($stageId) {
                                \App\Models\BoqTemplateStage::destroy($stageId);
                                session()->flash('success', 'Stage removed successfully.');
                            }
                            break;
                            
                        case 'remove_activity':
                            $activityId = $request->input('activity_id');
                            if ($activityId) {
                                // First remove all sub-activities and their materials
                                $activity = \App\Models\BoqTemplateActivity::with('templateSubActivities.subActivity.materials')->find($activityId);
                                if ($activity) {
                                    foreach ($activity->templateSubActivities as $subActivity) {
                                        // Remove materials first
                                        \App\Models\SubActivityMaterial::where('sub_activity_id', $subActivity->sub_activity_id)->delete();
                                        // Remove sub-activity
                                        $subActivity->delete();
                                    }
                                    // Remove the activity
                                    $activity->delete();
                                    session()->flash('success', 'Activity and all sub-activities removed successfully.');
                                } else {
                                    session()->flash('error', 'Activity not found.');
                                }
                            } else {
                                session()->flash('error', 'Activity ID not provided.');
                            }
                            break;
                            
                        case 'remove_sub_activity':
                            $subActivityId = $request->input('sub_activity_id');
                            if ($subActivityId) {
                                $subActivity = \App\Models\BoqTemplateSubActivity::find($subActivityId);
                                if ($subActivity) {
                                    // Remove all materials for this sub-activity first
                                    \App\Models\SubActivityMaterial::where('sub_activity_id', $subActivity->sub_activity_id)->delete();
                                    // Remove the sub-activity
                                    $subActivity->delete();
                                    session()->flash('success', 'Sub-activity and all materials removed successfully.');
                                } else {
                                    session()->flash('error', 'Sub-activity not found.');
                                }
                            } else {
                                session()->flash('error', 'Sub-activity ID not provided.');
                            }
                            break;
                            
                        case 'remove_material':
                            $materialId = $request->input('material_id');
                            if ($materialId) {
                                $material = \App\Models\SubActivityMaterial::find($materialId);
                                if ($material) {
                                    $materialName = $material->boqItem->name ?? 'Unknown Material';
                                    $material->delete();
                                    session()->flash('success', "Material '{$materialName}' removed successfully.");
                                } else {
                                    session()->flash('error', 'Material not found.');
                                }
                            } else {
                                session()->flash('error', 'Material ID not provided.');
                            }
                            break;
                    }
                    
                    // Redirect to refresh the page with updated data
                    return redirect()->route('hr_settings_boq_template_builder', ['templateId' => $templateId]);
                }
            }
        }
        
        if ($templateId) {
            \Log::info('BOQ Template Builder - Attempting to load template', [
                'template_id' => $templateId
            ]);
            
            $template = BoqTemplate::with(['templateStages.constructionStage', 'templateStages.templateActivities.activity', 'templateStages.templateActivities.templateSubActivities.subActivity.materials.boqItem', 'buildingType'])->find($templateId);
            
            \Log::info('BOQ Template Builder - Template lookup result', [
                'template_found' => $template ? true : false,
                'template_id_searched' => $templateId,
                'template_name' => $template ? $template->name : null,
                'template_class' => $template ? get_class($template) : null
            ]);
            
            if ($template) {
                // Get selected stages for this template
                $selectedStages = $template->templateStages->pluck('construction_stage_id')->toArray();
                
                // Calculate stats
                $templateStats = [
                    'stages' => $template->templateStages->count(),
                    'activities' => $template->templateStages->sum(function($stage) {
                        return $stage->templateActivities->count();
                    }),
                    'subActivities' => $template->templateStages->sum(function($stage) {
                        return $stage->templateActivities->sum(function($activity) {
                            return $activity->templateSubActivities->count();
                        });
                    })
                ];
            }
        }
        
        $data = [
            'templateId' => $templateId,
            'template' => $template,
            'constructionStages' => ConstructionStage::orderBy('sort_order')->get(), // All stages for children lookup
            'parentStages' => ConstructionStage::whereNull('parent_id')->orderBy('sort_order')->get(), // Only parents for Add Stage section
            'selectedStages' => $selectedStages,
            'templateStats' => $templateStats,
            'boqItems' => BoqTemplateItem::with('category')->orderBy('name')->get()
        ];
        
        \Log::info('BOQ Template Builder - Final data for view', [
            'templateId' => $data['templateId'],
            'template_is_null' => $data['template'] === null,
            'template_exists' => $data['template'] ? true : false,
            'template_name' => $data['template'] ? $data['template']->name : null,
            'construction_stages_count' => $data['constructionStages']->count(),
            'boq_items_count' => $data['boqItems']->count()
        ]);
        
        return view('pages.settings.boq_template_builder')->with($data);
    }

    public function boq_template_report($templateId)
    {
        // Load template with all necessary relationships
        $template = BoqTemplate::with([
            'templateStages.constructionStage',
            'templateStages.templateActivities.activity',
            'templateStages.templateActivities.templateSubActivities.subActivity.materials.boqItem',
            'buildingType'
        ])->find($templateId);

        if (!$template) {
            abort(404, 'Template not found');
        }

        $data = [
            'template' => $template
        ];

        return view('pages.settings.boq_template_report')->with($data);
    }

}
