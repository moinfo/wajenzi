<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\Sale;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
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
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $expense_categories = ExpensesCategory::all();
        $expense_sub_categories = ExpensesSubCategory::all();

        $data = [
            'expense_sub_categories' => $expense_sub_categories,
            'expense_categories' => $expense_categories,
            'supervisors' => $supervisors,
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
        $expense = \App\Models\Expense::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'expense' => $expense,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.expenses.expense')->with($data);
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
