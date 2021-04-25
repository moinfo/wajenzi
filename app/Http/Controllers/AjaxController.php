<?php


namespace App\Http\Controllers;
use App\Models\Bank;
use App\Models\Division;
use App\Models\Efd;
use App\Models\ExpensesCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Item;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\System;
use Illuminate\Http\Request;


class AjaxController
{
    public function index(Request $request, $fx = null) {
//        $fx = $request->has('fx') ? $request->get('fx') : null;
        if ($fx) {
            switch ($fx) {
                case 'form': // Load form from forms directory
                    $suppliers = Supplier::all();
                    $supervisors_and_drivers = Supervisor::all();
                    $supervisors = Supervisor::where('employee_id',1)->get();
                    $items = Item::all();
                    $banks = Bank::all();
                    $efds = Efd::all();
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
                    $expenses_categories = ExpensesCategory::all();
                    $financial_charge_categories = FinancialChargeCategory::all();
                    $data = $request->input('data') ?? [
                            'suppliers' => $suppliers,
                            'employees' => $employees,
                            'supervisors_and_drivers' => $supervisors_and_drivers,
                            'items' => $items,
                            'employee_types' => $employee_types,
                            'employment_types' => $employment_types,
                            'statuses' => $status,
                            'marital_status' => $marital_status,
                            'efds' => $efds,
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
                        $id = $request->input('id');
                        $data['object'] = $id ? $fullObject::find($id) : new $fullObject();
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
}
