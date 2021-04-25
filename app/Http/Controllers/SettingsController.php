<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\AdvanceSalary;
use App\Models\Allowance;
use App\Models\AllowanceSubscription;
use App\Models\Bank;
use App\Models\Deduction;
use App\Models\DeductionSetting;
use App\Models\DeductionSubscription;
use App\Models\Department;
use App\Models\Efd;
use App\Models\ExpensesCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffSalary;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\System;
use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
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
//            ['name'=>'Departments', 'route'=>'hr_settings_departments', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Supervisor', 'route'=>'hr_settings_supervisors', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Banks', 'route'=>'hr_settings_banks', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Systems', 'route'=>'hr_settings_systems', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Users', 'route'=>'hr_settings_users', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Positions', 'route'=>'hr_settings_positions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Roles', 'route'=>'hr_settings_roles', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Permissions', 'route'=>'hr_settings_permissions', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Suppliers', 'route'=>'hr_settings_suppliers', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Items', 'route'=>'hr_settings_items', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Expenses Categories', 'route'=>'hr_settings_expenses_categories', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Financial Charge Categories', 'route'=>'hr_settings_financial_charge_categories', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'EFD', 'route'=>'hr_settings_efd', 'icon' => 'si si-settings', 'badge' => 0],
        ];
        $data = [
            'settings' => $settings
        ];

        return view('pages.settings.settings_index')->with($data);
    }

    public function allowances(Request $request){
        if($this->handleCrud($request, 'Allowance')) {
            return back();
        }
        $data = [
            'allowances' => Allowance::all()
        ];
        return view('pages.settings.settings_allowances')->with($data);
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
        if($this->handleCrud($request, 'Supplier')) {
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
}
