<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Payroll extends Model implements ApprovableModel
{
    use HasFactory,Approvable;

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

    public static function isCurrentPayrollPaid($start_date,$end_date)
    {
        $records = PayrollRecord::select([DB::raw("*")])
            ->whereBetween('created_at',[$start_date,$end_date])
            ->get();
        if(count($records)){
            return true;
        }else{
            return false;
        }

    }

    public static function getIsPayrollOpened($month,$year)
    {
        $payroll = Payroll::where('month',$month)->where('year',$year)->get();
        if(count($payroll)){
            return true;
        }else{
            return false;
        }
    }



    public static function getTotalNetPaid($staff_id,$payroll_id)
    {
        return  PayrollNetSalary::Where('staff_id',$staff_id)->Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getDonePayroll($this_year)
    {
        return Payroll::select('month')->where('year',$this_year)->get()->toArray();
    }

    public static function getThisPayroll($month, $year)
    {
        return Payroll::where('month',$month)->where('year',$year)->get()->first();
    }
    public static function getThisPayrollApproved($month, $year)
    {
        return Payroll::where('status','APPROVED')->where('month',$month)->where('year',$year)->get()->first();
    }
    public static function countUnapproved()
    {
        return count(Payroll::where('status','!=','APPROVED')->where('status','!=','REJECTED')->get());
    }

    public function user(){
        return $this->belongsTo(User::class,'created_by_id','id');
    }
}
