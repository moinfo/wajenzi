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
use Illuminate\Http\Request;


class AjaxController
{
    public function index(Request $request, $fx = null) {
//        $fx = $request->has('fx') ? $request->get('fx') : null;
        if ($fx) {
            switch ($fx) {
                case 'form': // Load form from forms directory
                    $suppliers = Supplier::all();
                    $supervisors = Supervisor::all();
                    $items = Item::all();
                    $banks = Bank::all();
                    $efds = Efd::all();
                    $expenses_categories = ExpensesCategory::all();
                    $financial_charge_categories = FinancialChargeCategory::all();
                    $data = $request->input('data') ?? [
                            'suppliers' => $suppliers,
                            'items' => $items,
                            'efds' => $efds,
                            'banks' => $banks,
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
