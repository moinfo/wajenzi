<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\Sale;
use App\Models\Supervisor;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{

    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Expense')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $expenses_sub_category_id = $request->input('expenses_sub_category_id');
        $expenses_category_id = $request->input('expenses_category_id');

//        $expenses = DB::table('expenses')
//            ->select(['expenses.*','expenses_sub_categories.name as sub_category','expenses_categories.name as category'])
//            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id','left')
//            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id','left')
//            ->where('date','>=',$start_date)
//            ->where('date','<=',$end_date);
//        if($expenses_sub_category_id != 0){
//            $expenses->where('expenses_sub_category_id','=',$expenses_sub_category_id);
//        }
//        if($expenses_category_id != 0){
//            $expenses->where('expenses_sub_category_id','=',$expenses_category_id);
//        }
//        $expenses = $expenses->get();

        $expenses = Expense::all();


        $expense_categories = ExpensesCategory::all();
        $expense_sub_categories = ExpensesSubCategory::all();

        $data = [
            'expense_sub_categories' => $expense_sub_categories,
            'expense_categories' => $expense_categories,
            'expenses' => $expenses
        ];
        return view('pages.expenses.expenses_index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(Expense $expense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Expense $expense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy(Expense $expense)
    {
        //
    }

    public function expense($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'expense');

        $approval_data = \App\Models\Expense::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Expense Category' => $approval_data->expensesSubCategory->expensesCategory->name ?? $approval_data->category,
            'Expense Sub Category' => $approval_data->expensesSubCategory->name ?? $approval_data->sub_category,
            'Description' => $approval_data->description,
            'Amount' => number_format($approval_data->amount),
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Expenses',
//            'approval_data_name' => $approval_data->user->name,
            'approval_data_name' => $approval_data->user->name ?? '',
            'details' => $details,
            'model' => 'Expense',
            'route' => 'expense',

        ];
        return view('approvals._approve_page')->with($data);
    }

    public function search(Request $request){
        if($this->handleCrud($request, 'Expense')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $expenses_sub_category_id = $request->input('expenses_sub_category_id');
        $expenses_category_id = $request->input('expenses_category_id');

            $expenses = DB::table('expenses')
                ->select(['expenses.*','expenses_sub_categories.name as sub_category','expenses_categories.name as category'])
                ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date);
                if($expenses_sub_category_id != 0){
                    $expenses->where('expenses_sub_category_id','=',$expenses_sub_category_id);
                }
                if($expenses_category_id != 0){
                    $expenses->where('expenses_sub_category_id','=',$expenses_category_id);
                }
                $expenses = $expenses->get();


        $supervisors = Supervisor::all();
        $expense_categories = ExpensesCategory::all();
        $expense_sub_categories = ExpensesSubCategory::all();
        return view('pages.expenses.expenses_index',compact('expenses', 'expense_categories', 'expense_sub_categories'));
    }
}
