<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpensesCategory;
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

        $data = [
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

    public function search(Request $request){
        if($this->handleCrud($request, 'Expense')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supervisor_id = $request->input('supervisor_id');
        $expenses_category_id = $request->input('expenses_category_id');
        if($supervisor_id == 0 && $expenses_category_id == 0){
            $expenses = DB::table('expenses')
                ->join('supervisors', 'supervisors.id', '=', 'expenses.supervisor_id')
                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses.expenses_category_id')
                ->select('expenses.*','expenses_categories.name as category_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }elseif($supervisor_id != 0 && $expenses_category_id == 0){
            $expenses = DB::table('expenses')
                ->join('supervisors', 'supervisors.id', '=', 'expenses.supervisor_id')
                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses.expenses_category_id')
                ->select('expenses.*','expenses_categories.name as category_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supervisor_id','=',$supervisor_id)
                ->get();
        }elseif($supervisor_id == 0 && $expenses_category_id != 0){
            $expenses = DB::table('expenses')
                ->join('supervisors', 'supervisors.id', '=', 'expenses.supervisor_id')
                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses.expenses_category_id')
                ->select('expenses.*','expenses_categories.name as category_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('expenses_category_id','=',$expenses_category_id)
                ->get();
        }
        else{
            $expenses = DB::table('expenses')
                ->join('supervisors', 'supervisors.id', '=', 'expenses.supervisor_id')
                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses.expenses_category_id')
                ->select('expenses.*','expenses_categories.name as category_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supervisor_id','=',$supervisor_id)
                ->where('expenses_category_id','=',$expenses_category_id)
                ->get();
        }

        $supervisors = Supervisor::all();
        $expense_categories = ExpensesCategory::all();
        return view('pages.expenses.expenses_index',compact('expenses','supervisors', 'expense_categories'));
    }
}
