<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollRecord extends Model
{
    use HasFactory;

    public static function getTotalNetSalaryPerDay($date)
    {
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount")])
                ->Where('status','APPROVED')->whereDate('created_at',$date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalNetSalary($start_date,$end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount")])
                ->Where('status','APPROVED')-> whereDate('created_at','>=',$start_date)
                ->whereDate('created_at','<=',$end_date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalNetSalaryAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount, DATE(created_at) as date")])->Where('status','APPROVED')->WhereBetween(DB::raw('date(created_at)'),[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalAllowance($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(allowance) as total_amount")])
                ->Where('status','APPROVED')-> whereDate('created_at','>=',$start_date)
                ->whereDate('created_at','<=',$end_date)->get()->first()['total_amount'] ?? 0;
    }

    public function getCurrentPayroll($start_date, $end_date)
    {
      return  $records = PayrollRecord::whereBetween('created_at',[$start_date,$end_date])
          ->select([DB::raw("*")])->get();
    }

//    public static function getTotalNetSalaryPerDay($date)
//    {
//        return PayrollRecord::select([DB::raw("SUM(net) as total_amount")])
//            ->Where('status','APPROVED')->whereDate('created_at',$date)->get()->first()['total_amount'] ?? 0;
//    }

//    public static function getTotalNetSalaryAmountFromBeginning($end_date)
//    {
//        $start_date = '2020-01-01';
//        return PayrollRecord::select([DB::raw("SUM(net) as total_amount, DATE(created_at) as date")])->Where('status','APPROVED')->WhereBetween(DB::raw('date(created_at)'),[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
//    }

    public static function getPayrollRecord($start_date,$end_date)
    {
        return PayrollRecord::whereBetween('date',[$start_date,$end_date])->get()->first();
    }


//    public function getCurrentPayroll($month, $year)
//    {
//        return  $records = PayrollRecord::where('month',$month)->where('year',$year)->select([DB::raw("*")])->get();
//    }


    public static function getTotalBasicSalary($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(basicSalary) as basic_salary")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['basic_salary'];
    }

//    public static function getTotalAllowance($start_date, $end_date)
//    {
//        return PayrollRecord::select([DB::raw("SUM(allowance) as allowance")])
//            ->whereBetween('date',[$start_date,$end_date])
//            ->get()->first()['allowance'];
//    }
    public static function getTotalNet($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(net) as net")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['net'];
    }

    public static function getTotalSDL($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(sdl) as sdl")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['sdl'];
    }

    public static function getTotalDeduction($start_date, $end_date,$type)
    {
        return PayrollRecord::select([DB::raw("SUM($type) as $type")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()["$type"];
    }

    public static function getTotalPAYE($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(paye) as paye")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['paye'];
    }

    public static function getTotalWCF($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(wpf) as wpf")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['wpf'];
    }

    public static function getTotalAdvanceSalary($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(advanceSalary) as advanceSalary")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['advanceSalary'];
    }

    public static function getTotalLoanDeduction($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(loanDeduction) as loanDeduction")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['loanDeduction'];
    }

    public static function getTotalHESLB($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(heslb) as heslb")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['heslb'];
    }

    public static function getTotalNSSF($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(employeePension) as employeePension")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['employeePension'];
    }

    public static function getTotalNHIFEmployee($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(employeeHealth) as employeeHealth")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['employeeHealth'];
    }

    public static function getTotalNHIFEmployer($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(employerHealth) as employerHealth")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['employerHealth'];
    }

    public static function getTotalNSSFEmployee($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(employeePension) as employeePension")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['employeePension'];
    }

    public static function getTotalNSSFEmployer($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(employerPension) as employerPension")])
            ->whereBetween('date',[$start_date,$end_date])
            ->get()->first()['employerPension'];
    }

}
