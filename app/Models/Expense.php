<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory;
    public $fillable = ['id', 'expenses_sub_category_id', 'amount', 'description', 'date', 'file', 'status'];

    public function expensesCategory(){
        return $this->belongsTo(ExpensesCategory::class);
    }
    public function expensesSubCategory(){
        return $this->belongsTo(ExpensesSubCategory::class);
    }
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }

    public static function getTotalAdministrativeExpenses($start_date,$end_date){
     return   Expense::select(DB::raw("SUM(amount) as total_amount"))
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
            ->where('expenses_sub_categories.expenses_category_id','=',1)
            ->WhereBetween('expenses.date',[$start_date,$end_date])
            ->Where('expenses.status','APPROVED')
             ->get()->first()['total_amount'];
    }
    public static function getTotalExpensesGroupByExpensesCategory($start_date,$end_date){
     return   Expense::select(DB::raw("SUM(expenses.amount) as total_amount"),"expenses_categories.name as expense_name")
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
            ->WhereBetween('expenses.date',[$start_date,$end_date])
             ->Where('expenses.status','APPROVED')
             ->groupBy('expenses_categories.id')
            ->get();
    }

    public static function getTotalFinancialCharges($start_date,$end_date){
     return   Expense::select(DB::raw("SUM(amount) as total_amount"))
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
            ->where('expenses_sub_categories.expenses_category_id','=',2)
            ->WhereBetween('expenses.date',[$start_date,$end_date])
            ->get()->first()['total_amount'];
    }

    public static function getTotalDepreciation($start_date,$end_date){
     return   Expense::select(DB::raw("SUM(amount) as total_amount"))
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
            ->where('expenses_sub_categories.expenses_category_id','=',3)
            ->WhereBetween('expenses.date',[$start_date,$end_date])
            ->Where('expenses.status','APPROVED')
            ->get()->first()['total_amount'];
    }

    public static function getTotalExpense($start_date,$end_date){
     return   Expense::select(DB::raw("SUM(amount) as total_amount"))->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'];
    }

    public static function getTotalExpensesInFinancial($start_date,$end_date){
        return self::getTotalExpense($start_date,$end_date);
    }

}
