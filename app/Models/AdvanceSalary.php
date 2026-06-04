<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class AdvanceSalary extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'description', 'status', 'create_by_id','document_number', 'monthly_deduction', 'start_month', 'start_year'];

    /**
     * Whether this advance is recovered via a fixed monthly payment plan.
     */
    public function hasPlan(): bool
    {
        return !is_null($this->monthly_deduction) && $this->monthly_deduction > 0;
    }

    /**
     * Total amount already recovered through payroll deductions for this advance.
     */
    public function amountRecovered()
    {
        return PayrollAdvanceSalary::where('advance_salary_id', $this->id)->sum('amount');
    }

    /**
     * Outstanding balance still to be deducted.
     */
    public function remainingBalance()
    {
        return max(0, $this->amount - $this->amountRecovered());
    }

    /**
     * Whether the plan has started by the given payroll year/month.
     */
    public function planStarted($year, $month): bool
    {
        return ($this->start_year < $year)
            || ($this->start_year == $year && $this->start_month <= $month);
    }


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

    public static function getTotalAdvanceSalaryPerDay($date)
    {
        return AdvanceSalary::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }


    public function user(){
        return $this->belongsTo(User::class, 'create_by_id');
    }

    public static function countUnapproved()
    {
        return count(AdvanceSalary::where('status','!=','APPROVED')->where('status','!=','REJECTED')->get());
    }


    public static function getTotalAdvanceSalaryAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return AdvanceSalary::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public function staff(){
        return $this->belongsTo(User::class);
    }





}
