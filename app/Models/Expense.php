<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class Expense extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'expenses_sub_category_id', 'amount', 'description', 'date', 'file', 'status','document_number'];


    /**
     * Logic executed when the approval process is completed.
     *
     * This method handles the state transitions based on your application's status values:
     * 'CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {

        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

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
    public static function getTotalExpensesGroupByExpensesCategory($start_date,$end_date,$category){
      $expenses =  Expense::select(DB::raw("SUM(expenses.amount) as total_amount"))
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id','left')
            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id','left')
            ->WhereBetween('expenses.date',[$start_date,$end_date])
             ->Where('expenses.status','APPROVED');
         if($category){
             $expenses->Where('expenses_sub_categories.expenses_category_id',$category);
         }

        return $expenses ->groupBy('expenses_sub_categories.expenses_category_id')->get()->first()['total_amount'];
    }
    public static function getTotalExpensesGroupBySubExpensesCategory($start_date,$end_date,$sub_category_id){
        $expenses = Expense::select(DB::raw("SUM(expenses.amount) as total_amount"))
            ->WhereBetween('expenses.date',[$start_date,$end_date])
             ->Where('expenses.status','APPROVED');
                if($sub_category_id){
                    $expenses->Where('expenses.expenses_sub_category_id',$sub_category_id);
                }

        return $expenses->get()->first()['total_amount'];
    }
    public static function getTotalExpensesGroupBySubExpensesCategoryOnlyFinancial($start_date,$end_date){
        $expenses = Expense::select(DB::raw("SUM(expenses.amount) as total_amount"))
            ->join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
            ->WhereBetween('expenses.date',[$start_date,$end_date])
             ->Where('expenses.status','APPROVED')
        ->Where('expenses_sub_categories.is_financial','YES');

        return $expenses->get()->first()['total_amount'];
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
