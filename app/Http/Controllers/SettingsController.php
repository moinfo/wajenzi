<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\Allowance;
use App\Models\Bank;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Efd;
use App\Models\ExpensesCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Item;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request){
        $settings = [
//            ['name'=>'Staff Allowances', 'route'=>'hr_settings_allowances', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Departments', 'route'=>'hr_settings_departments', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Supervisor', 'route'=>'supervisor', 'icon' => 'si si-settings', 'badge' => 0],
            ['name'=>'Banks', 'route'=>'hr_settings_banks', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Positions', 'route'=>'hr_settings_positions', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Roles', 'route'=>'hr_settings_roles', 'icon' => 'si si-settings', 'badge' => 0],
//            ['name'=>'Permissions', 'route'=>'hr_settings_permissions', 'icon' => 'si si-settings', 'badge' => 0],
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
