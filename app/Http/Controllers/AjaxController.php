<?php


namespace App\Http\Controllers;
use App\Models\AccountType;
use App\Models\Allowance;
use App\Models\AllowanceSubscription;
use App\Models\ApprovalDocumentType;
use App\Models\Asset;
use App\Models\Bank;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAccount;
use App\Models\BongeCustomer;
use App\Models\Category;
use App\Models\ChartAccount;
use App\Models\ClientSource;
use App\Models\CostCategory;
use App\Models\Currency;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Division;
use App\Models\Efd;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Item;
use App\Models\Payroll;
use App\Models\PayrollType;
use App\Models\ProcessApprovalFlow;
use App\Models\ProcessApprovalFlowStep;
use App\Models\Project;

// BOQ Template Models
use App\Models\Activity;
use App\Models\SubActivity;
use App\Models\BoqTemplate;
use App\Models\ConstructionStage;
use App\Models\BuildingType;
use App\Models\BoqItemCategory;
use App\Models\ProjectClient;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\SubCategory;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\SupplierTarget;
use App\Models\System;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Wakala;
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
                    $in_date = date('Y-m-d');
                    $withdraw_suppliers = Supplier::where('is_withdraw','YES')->get();
                    $suppliers = Supplier::all();
                    $suppliers_with_balances = Supplier::select('*','suppliers.id AS supplier_id')->join('supplier_targets','supplier_targets.supplier_id','=','suppliers.id')->where('supplier_targets.date',$in_date)->orderBy('supplier_depend_on_system', 'DESC')->get();
                    $transfer_suppliers_with_balances = Supplier::orderBy('supplier_depend_on_system', 'DESC')->get();
                    $kassim_supplier = Supplier::where('is_transferred','YES')->get();
                    $assets = Asset::all();
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
                    $staffs = Staff::onlyStaffs();;
                    $systems= System::all();

                    // BOQ Template System Data
                    $construction_stages = ConstructionStage::orderBy('sort_order')->get();
                    $parent_construction_stages = ConstructionStage::whereNull('parent_id')->orderBy('name')->get();
                    $activities = Activity::with('constructionStage')->orderBy('sort_order')->get();
                    $building_types = BuildingType::where('is_active', true)->orderBy('name')->get();
                    $parent_building_types = BuildingType::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
                    $boq_item_categories = BoqItemCategory::orderBy('name')->get();
                    $parent_boq_item_categories = BoqItemCategory::whereNull('parent_id')->orderBy('name')->get();
                    $skill_levels = [
                        ['name' => 'unskilled'],
                        ['name' => 'semi_skilled'],
                        ['name' => 'skilled'],
                        ['name' => 'specialist']
                    ];
                    $duration_units = [
                        ['name' => 'hours'],
                        ['name' => 'days'],
                        ['name' => 'weeks']
                    ];
                    $employees = [
                        ['id'=>'1','name'=>'Supervisor'],
                        ['id'=>'2','name'=>'Driver']
                    ];
                    $transfers = [
                        ['id'=>'1','name'=>'NO'],
                        ['id'=>'2','name'=>'YES'],
                        ['id'=>'3','name'=>'CAN BE BOTH']
                    ];
                    $supplier_depend_on_systems = [
                        ['id'=>'1','name'=>'WHITESTAR'],
                        ['id'=>'2','name'=>'BONGE']
                    ];
                    $bank_reconciliation_payment_types = [
                        ['name'=>'SALES'],
                        ['name'=>'OFFICE']
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
//                        ['id'=>'1','name'=>'CREATE'],
                        ['id'=>'2','name'=>'CHECK'],
                        ['id'=>'3','name'=>'VERIFY'],
                        ['id'=>'4','name'=>'APPROVE'],
//                        ['id'=>'5','name'=>'AUTHORIZE']
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
                    $billing_cycles = [
                        ['id'=>'0','name'=>'One Time'],
                        ['id'=>'1','name'=>'Annually'],
                        ['id'=>'3','name'=>'Quarterly'],
                        ['id'=>'6','name'=>'Semi-Annually'],
                        ['id'=>'12','name'=>'Monthly']
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
                    $supplier_types = [
                        ['name'=>'INDIRECT'],
                        ['name'=>'DIRECT']
                    ];
                    $is_withdraws = [
                        ['name'=>'YES'],
                        ['name'=>'NO']
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
                    $supplier_target_types = [
                        ['name'=>'TARGET'],
                        ['name'=>'COMMISSION']
                    ];
                    $slip_presentations = [
                        ['name'=>'yes'],
                        ['name'=>'no']
                    ];
                    $allowance_types = [
                        ['name'=>'DAILY'],
                        ['name'=>'MONTHLY']
                    ];

                    $users = User::all();
                    $expenses_categories = ExpensesCategory::all();
                    $expenses_sub_categories = ExpensesSubCategory::all();
                    $financial_charge_categories = FinancialChargeCategory::all();
                    $beneficiaries = Beneficiary::all();
                    $beneficiary_accounts = BeneficiaryAccount::all();
                    $wakalas = Wakala::all();
                    $start_date = date('Y-m-d');
                    $todayTargets = SupplierTarget::getTodayTargets($start_date);
                    $project_clients = ProjectClient::where('status','APPROVED')->get();
                    $projects = Project::where('status','APPROVED')->get();
                    $project_types = ProjectType::all();
                    $cost_categories = CostCategory::orderBy('name')->get();
                    $departments = Department::all();
                    $payroll_types = PayrollType::all();
                    $client_sources = ClientSource::all();
                    $account_types = AccountType::all();
                    $foreign_currencies = Currency::where('is_base','NO')->get();
                    $base_currencies = Currency::where('is_base','YES')->get();
                    $chart_of_accounts = ChartAccount::with(['accountType', 'parentAccount'])->get();
                    $currencies = Currency::all();
                    $charts_accounts = ChartAccount::all();
                    $charts_account_petty_cashs = ChartAccount::where('id',12)->get();
                    $roles = Role::all();
                    $process_approval_flows = ProcessApprovalFlow::all();
                    $process_approval_flow_steps = ProcessApprovalFlowStep::all();

                    $data = $request->input('data') ?? [
                            'construction_stages' => $construction_stages,
                            'parent_construction_stages' => $parent_construction_stages,
                            'activities' => $activities,
                            'building_types' => $building_types,
                            'parent_building_types' => $parent_building_types,
                            'boq_item_categories' => $boq_item_categories,
                            'parent_boq_item_categories' => $parent_boq_item_categories,
                            'skill_levels' => $skill_levels,
                            'duration_units' => $duration_units,
                            'in_date' => $in_date,
                            'roles' => $roles,
                            'process_approval_flows' => $process_approval_flows,
                            'process_approval_flow_steps' => $process_approval_flow_steps,
                            'chart_of_accounts' => $chart_of_accounts,
                            'charts_accounts' => $charts_accounts,
                            'charts_account_petty_cashs' => $charts_account_petty_cashs,
                            'currencies' => $currencies,
                            'foreign_currencies' => $foreign_currencies,
                            'base_currencies' => $base_currencies,
                            'projects' => $projects,
                            'allowance_types' => $allowance_types,
                            'account_types' => $account_types,
                            'payroll_types' => $payroll_types,
                            'client_sources' => $client_sources,
                            'departments' => $departments,
                            'projectTypes' => $project_types,
                            'cost_categories' => $cost_categories,
                            'clients' => $project_clients,
                            'slip_presentations' => $slip_presentations,
                            'todayTargets' => $todayTargets,
                            'beneficiaries' => $beneficiaries,
                            'beneficiary_accounts' => $beneficiary_accounts,
                            'wakalas' => $wakalas,
                            'expenses_sub_categories' => $expenses_sub_categories,
                            'suppliers' => $suppliers,
                            'supplier_target_types' => $supplier_target_types,
                            'suppliers_with_balances' => $suppliers_with_balances,
                            'transfer_suppliers_with_balances' => $transfer_suppliers_with_balances,
                            'supplier_types' => $supplier_types,
                            'is_withdraws' => $is_withdraws,
                            'users' => $users,
                            'withdraw_suppliers' => $withdraw_suppliers,
                            'kassim_supplier' => $kassim_supplier,
                            'transfers' => $transfers,
                            'supplier_depend_on_systems' => $supplier_depend_on_systems,
                            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
                            'assets' => $assets,
                            'billing_cycles' => $billing_cycles,
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
                            $data['object']->Where("status",'APPROVED');
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
                    
                    // Special handling for BOQ template details
                    if ($form_name === 'boq_template_details' && isset($data['object']) && $data['object'] instanceof \App\Models\BoqTemplate) {
                        $template = $data['object'];
                        
                        // Load template with relationships for statistics calculation
                        $template->load([
                            'templateStages.templateActivities.templateSubActivities.subActivity.materials'
                        ]);
                        
                        // Calculate template statistics
                        $data['templateStats'] = [
                            'stages' => $template->templateStages->count(),
                            'activities' => $template->templateStages->sum(function($stage) {
                                return $stage->templateActivities->count();
                            }),
                            'subActivities' => $template->templateStages->sum(function($stage) {
                                return $stage->templateActivities->sum(function($activity) {
                                    return $activity->templateSubActivities->count();
                                });
                            }),
                            'materials' => $template->templateStages->sum(function($stage) {
                                return $stage->templateActivities->sum(function($activity) {
                                    return $activity->templateSubActivities->sum(function($subActivity) {
                                        return $subActivity->subActivity->materials->count();
                                    });
                                });
                            })
                        ];
                    }
                    
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

                // BOQ Template Builder AJAX Methods
                case 'getActivitiesForStages':
                    $stageIds = $request->input('stage_ids', []);
                    $activities = Activity::with('constructionStage')
                        ->whereIn('construction_stage_id', $stageIds)
                        ->orderBy('sort_order')
                        ->get();
                    return response()->json(['activities' => $activities]);
                    break;

                case 'getSubActivitiesForActivities':
                    $activityIds = $request->input('activity_ids', []);
                    $subActivities = SubActivity::with('activity')
                        ->whereIn('activity_id', $activityIds)
                        ->orderBy('sort_order')
                        ->get();
                    return response()->json(['subActivities' => $subActivities]);
                    break;

                case 'previewBoqTemplate':
                    $selectedStages = $request->input('selected_stages', []);
                    $selectedActivities = $request->input('selected_activities', []);
                    $selectedSubActivities = $request->input('selected_sub_activities', []);

                    $stages = ConstructionStage::with([
                        'activities' => function($query) use ($selectedActivities) {
                            $query->whereIn('id', $selectedActivities);
                        },
                        'activities.subActivities' => function($query) use ($selectedSubActivities) {
                            $query->whereIn('id', $selectedSubActivities);
                        }
                    ])->whereIn('id', $selectedStages)->orderBy('sort_order')->get();

                    $html = view('partials.boq_template_preview', compact('stages'))->render();
                    return response()->json(['html' => $html]);
                    break;

                case 'saveBoqTemplateConfiguration':
                    $templateId = $request->input('template_id');
                    $selectedStages = $request->input('selected_stages', []);
                    $selectedActivities = $request->input('selected_activities', []);
                    $selectedSubActivities = $request->input('selected_sub_activities', []);

                    try {
                        $template = BoqTemplate::findOrFail($templateId);

                        // Clear existing template configuration
                        $template->templateStages()->delete();

                        // Add selected stages, activities, and sub-activities
                        foreach ($selectedStages as $index => $stageId) {
                            $templateStage = $template->templateStages()->create([
                                'construction_stage_id' => $stageId,
                                'sort_order' => $index + 1
                            ]);

                            // Add activities for this stage
                            $stageActivities = array_filter($selectedActivities, function($activityId) use ($stageId) {
                                $activity = Activity::find($activityId);
                                return $activity && $activity->construction_stage_id == $stageId;
                            });

                            foreach ($stageActivities as $actIndex => $activityId) {
                                $templateActivity = $templateStage->templateActivities()->create([
                                    'activity_id' => $activityId,
                                    'sort_order' => $actIndex + 1
                                ]);

                                // Add sub-activities for this activity
                                $activitySubActivities = array_filter($selectedSubActivities, function($subActivityId) use ($activityId) {
                                    $subActivity = SubActivity::find($subActivityId);
                                    return $subActivity && $subActivity->activity_id == $activityId;
                                });

                                foreach ($activitySubActivities as $subIndex => $subActivityId) {
                                    $templateActivity->templateSubActivities()->create([
                                        'sub_activity_id' => $subActivityId,
                                        'sort_order' => $subIndex + 1
                                    ]);
                                }
                            }
                        }

                        return response()->json(['success' => true, 'message' => 'Template configuration saved successfully']);
                    } catch (\Exception $e) {
                        return response()->json(['success' => false, 'message' => $e->getMessage()]);
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
