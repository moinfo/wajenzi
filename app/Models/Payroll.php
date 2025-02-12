<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payroll extends Model
{
    use HasFactory;

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
