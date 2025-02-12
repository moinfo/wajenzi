<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollAllowance extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','payroll_id','allowance_id','amount'];

    public static function getStaffAllowancePaid($staff_id, $payroll_id)
    {
        return  PayrollAllowance::Where('staff_id',$staff_id)->Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getAllowancePaid($payroll_id)
    {
        return  PayrollAllowance::Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getPayrollList($start_date,$end_date){
        return Payroll::whereBetween('submitted_date',[$start_date,$end_date])->where('status','APPROVED')->get();
    }


    public static function getTotalAllowance($start_date,$end_date){
        $payroll_lists = self::getPayrollList($start_date,$end_date);
        $total_allowance = 0;
        foreach ($payroll_lists as $index => $payroll_list) {
            $payroll_id = $payroll_list->id;
            $allowance = \App\Models\PayrollAllowance::getAllowancePaid($payroll_id);
            $total_allowance += $allowance;
        }
        return $total_allowance;
    }
}
