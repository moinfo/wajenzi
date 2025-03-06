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
use App\Models\Item;
use App\Models\LeaveType;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\Position;
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

    public function charts_of_accounts(Request $request){
        if($this->handleCrud($request, 'ChartAccount')) {
            return back();
        }
        $data = [
            'chart_of_accounts' => ChartAccount::all(),
            'account_types' => AccountType::all(),
        ];
        return view('pages.settings.settings_charts_of_accounts')->with($data);
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


    public function statutory_payments(Request $request){
        if($this->handleCrud($request, 'StatutoryPayment')) {
            return back();
        }
        $data = [
            'statutory_payments' => StatutoryPayment::all()
        ];
        return view('pages.settings.settings_statutory_payments')->with($data);
    }


    public function statutory_payment(Request $request,$id,$document_type_id){
//        dump($id);
//        return;
        $statutory_payments = \App\Models\StatutoryPayment::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'statutory_payments' => $statutory_payments,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.statutory_payment.statutory_payment')->with($data);
    }

    public function advance_salary($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'advance_salaries');

        // Get timeline data
        $timeline = $this->approvalService->getApprovalTimeline($document_type_id, $id);

        $approval_data = \App\Models\AdvanceSalary::where('id',$id)->get()->first();

        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;

        $details = [
            'Total Amount' => number_format($approval_data->amount),
            'Description' => $approval_data->description,
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'timeline' => $timeline,
            'approval_data' => $approval_data,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
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

        // Get timeline data
        $timeline = $this->approvalService->getApprovalTimeline($document_type_id, $id);

        $approval_data = \App\Models\Loan::where('id',$id)->get()->first();

        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;

        $details = [
            'Total Amount' => number_format($approval_data->amount),
            'Description' => $approval_data->description,
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'timeline' => $timeline,
            'approval_data' => $approval_data,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
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

}
