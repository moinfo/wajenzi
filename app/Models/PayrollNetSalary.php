<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollNetSalary extends Model
{
    use HasFactory;

    public $fillable = ['staff_id','payroll_id','amount'];

    public static function getPayrollList($start_date,$end_date){
        return Payroll::whereBetween('submitted_date',[$start_date,$end_date])->where('status','APPROVED')->get();
    }

    public static function getStaffNetPaid($staff_id,$payroll_id)
    {
        return  PayrollNetSalary::Where('staff_id',$staff_id)->Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalNetSalaryByPayroll($payroll_id)
    {
        return  PayrollNetSalary::Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalNetPayroll($start_date,$end_date){
        $payroll_lists = self::getPayrollList($start_date,$end_date);
        $total_net = 0;
        foreach ($payroll_lists as $index => $payroll_list) {
            $payroll_id = $payroll_list->id;
            $net = \App\Models\PayrollNetSalary::getTotalNetSalaryByPayroll($payroll_id);
            $total_net += $net;
        }
        return $total_net;
    }

    public function staff(){
        return $this->belongsTo(User::class);
    }
    public function payroll(){
        return $this->belongsTo(Payroll::class);
    }
}
