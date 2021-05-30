<?php


namespace App\Http\Controllers;
use App\Models\Allowance;
use App\Models\AllowanceSubscription;
use App\Models\ApprovalDocumentType;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Deduction;
use App\Models\Division;
use App\Models\Efd;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Item;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\SubCategory;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\System;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AjaxController
{
    public function index(Request $request, $fx = null) {
//        $fx = $request->has('fx') ? $request->get('fx') : null;
       // dd();
        if ($fx) {
            switch ($fx) {
                case 'form': // Load form from forms directory
                    $suppliers = Supplier::all();
                    $user_groups = UserGroup::all();
                    $approval_document_types = ApprovalDocumentType::all();
                    $categories = Category::all();
                    $sub_categories = SubCategory::all();
                    $supervisors_and_drivers = Supervisor::all();
                    $supervisors = Supervisor::where('employee_id',1)->get();
                    $items = Item::all();
                    $banks = Bank::all();
                    $efds = Efd::all();
                    $allowance_subscriptions = Allowance::all();
                    $deduction_subscriptions = Deduction::all();
                    $staffs = Staff::getList();
                    $systems= System::all();
                    $employees = [
                        ['id'=>'1','name'=>'Supervisor'],
                        ['id'=>'2','name'=>'Driver']
                    ];
                    $genders = [
                        ['name'=>'MALE'],
                        ['name'=>'FEMALE']
                    ];
                    $employee_types = [
                        ['name'=>'STAFF'],
                        ['name'=>'INTERN'],
                        ['name'=>'EXTERNAL']
                    ];
                    $purchases_types = [
                        ['id'=>'1','name'=>'VAT'],
                        ['id'=>'2','name'=>'EXEMPT']
                    ];
                    $actions = [
                        ['id'=>'1','name'=>'CREATE'],
                        ['id'=>'2','name'=>'CHECK'],
                        ['id'=>'3','name'=>'VERIFY'],
                        ['id'=>'4','name'=>'APPROVE'],
                        ['id'=>'5','name'=>'AUTHORIZE']
                    ];
                    $status = [
                        ['id'=>'1','name'=>'CREATED'],
                        ['id'=>'2','name'=>'PENDING'],
                        ['id'=>'3','name'=>'PAID'],
                        ['id'=>'4','name'=>'REJECTED'],
                        ['id'=>'5','name'=>'APPROVED'],
                        ['id'=>'6','name'=>'COMPLETED']
                    ];
                    $payment_types = [
                        ['id'=>'1','name'=>'System'],
                        ['id'=>'2','name'=>'Office']
                    ];
                    $permissions = [
                        ['name'=>'MENU'],
                        ['name'=>'SETTING'],
                        ['name'=>'REPORT'],
                        ['name'=>'CRUD']
                    ];
                    $natures = [
                        ['name'=>'GROSS'],
                        ['name'=>'NET'],
                        ['name'=>'TAXABLE']
                    ];
                    $employment_types = [
                        ['name'=>'FULL_TIME'],
                        ['name'=>'CONTRACT'],
                        ['name'=>'INTERN']
                    ];
                    $marital_status = [
                        ['name'=>'SINGLE'],
                        ['name'=>'MARRIED'],
                        ['name'=>'DIVORCED'],
                        ['name'=>'OTHER']
                    ];
                    $status = [
                        ['name'=>'ACTIVE'],
                        ['name'=>'INACTIVE'],
                        ['name'=>'DORMANT']
                    ];
                    $stock_types = [
                        ['name'=>'OPENING'],
                        ['name'=>'CLOSING']
                    ];
                    $users = Staff::all();
                    $expenses_categories = ExpensesCategory::all();
                    $expenses_sub_categories = ExpensesSubCategory::all();
                    $financial_charge_categories = FinancialChargeCategory::all();
                    $data = $request->input('data') ?? [
                            'expenses_sub_categories' => $expenses_sub_categories,
                            'suppliers' => $suppliers,
                            'users' => $users,
                            'stock_types' => $stock_types,
                            'user_groups' => $user_groups,
                            'payment_types' => $payment_types,
                            'categories' => $categories,
                            'status' => $status,
                            'actions' => $actions,
                            'approval_document_types' => $approval_document_types,
                            'employees' => $employees,
                            'sub_categories' => $sub_categories,
                            'natures' => $natures,
                            'purchases_types' => $purchases_types,
                            'permissions' => $permissions,
                            'supervisors_and_drivers' => $supervisors_and_drivers,
                            'items' => $items,
                            'employee_types' => $employee_types,
                            'employment_types' => $employment_types,
                            'deduction_subscriptions' => $deduction_subscriptions,
                            'allowance_subscriptions' => $allowance_subscriptions,
                            'statuses' => $status,
                            'marital_status' => $marital_status,
                            'efds' => $efds,
                            'staffs' => $staffs,
                            'banks' => $banks,
                            'systems' => $systems,
                            'genders' => $genders,
                            'supervisors' => $supervisors,
                            'expenses_categories' => $expenses_categories,
                            'financial_charge_categories' => $financial_charge_categories,
                        ];
                    $object = $request->has('className') ? ucfirst($request->input('className')) : null;
                    $metadata = $request->has('metadata') ? $request->input('metadata') : [];
                    if($object) {
                        $fullObject = 'App\Models\\' . $object;
                       // dd($fullObject);
                        $id = $request->input('id');
                        $key_name = $request->input('key_name') ?? null;
                        $date_find = $request->input('date_find') ?? null;
                        if($date_find != null && $date_find != '1970-01-01'){
                            $data['object'] = $fullObject::select([DB::raw("*")]);
                            if($date_find){
                                $data['object']->Where('date',$date_find);
                            }
                            if ($key_name){
                                $data['object']->Where("$key_name",$id);
                            }
                            $data['object'] = $data['object']->get();
                        }else{
                            $data['object'] = $id ? $fullObject::find($id) : new $fullObject();
                        }
                    }
                    if(count($metadata)){
                        foreach ($metadata as $fieldName => $className) {
                            $fullClassName = 'App\Models\\' . $className;
                            $method = 'all';
                           $data[$fieldName] = $fullClassName::$method();
                        }
                    }
                    $form_name = $request->input('formName');
                    return $form_name ? view('forms.' . strtolower($form_name))->with($data) : "<span class='alert'>Invalid Form Name</span>";
                    break;
                case 'class':
                    $object = ucfirst($request->input('className'));
                    $fullObject = 'App\Models\\' . $object;
                    $method = $request->input('method');
                    $params = $request->input('params') ?? [];
                    $id = $request->input('id');
                    if ($id) { // instance
                        $obj = $fullObject::find($id);
                        return $obj->$method(...$params);
                    } else {
                        return $fullObject::$method(...$params);
                    }
                    break;
                default:
                    return ('YES');
                    break;
            }
        } else {
            return 'INVALID OPERATION';
        }
    }
    public function ajaxRequestPost(Request $request)
    {
        $controller = new Controller();
        $data = json_decode($request['TableData'],true);
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
       if(Payroll::isCurrentPayrollPaid($start_date,$end_date)){
           return 0;
       }else{
           $save = DB::table('payroll_records')->insert($data) ?? [];
           return $save;
       }
    }

}
