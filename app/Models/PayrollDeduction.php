<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollDeduction extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','payroll_id','deduction_id','deduction_source','employee_deduction_amount','employer_deduction_amount'];

    public static function getTotalDeductionByPayrollByDeduction($payroll_id,$deduction_id,$employee_deduction_type)
    {
        if($employee_deduction_type == 'employee'){
            return  PayrollDeduction::Where('payroll_id',$payroll_id)->Where('deduction_id',$deduction_id)->select([DB::raw("SUM(employer_deduction_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
        }else{
            return  PayrollDeduction::Where('payroll_id',$payroll_id)->Where('deduction_id',$deduction_id)->select([DB::raw("SUM(employee_deduction_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
        }
    }

    public static function getTotalAmountDeductionByPayrollByDeduction($start_date,$end_date,$deduction_id,$employee_deduction_type){
        $payroll_lists = PayrollAllowance::getPayrollList($start_date,$end_date);
        $total_net = 0;
        foreach ($payroll_lists as $index => $payroll_list) {
            $payroll_id = $payroll_list->id;
            $net = self::getTotalDeductionByPayrollByDeduction($payroll_id,$deduction_id,$employee_deduction_type);
            $total_net += $net;
        }
        return $total_net;
    }

    public static function getTotalSDL($start_date, $end_date)
    {
        return DB::connection('mysql7')->table('lemuru.payroll_records')
        ->select([DB::raw("SUM(sdl) as sdl")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()->sdl;
    }
}
