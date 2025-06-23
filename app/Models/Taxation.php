<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxation extends Model
{
    use HasFactory;

    public static function ProfitFromOperatingActivitiesBeforeTaxation($start_date,$end_date){
        $gross = Gross::getTotalGrossProfit($start_date,$end_date);
        $nssf = PayrollRecord::getTotalNSSFEmployer($start_date,$end_date) ?? 0;
        $sdl = PayrollRecord::getTotalSDL($start_date,$end_date) ?? 0;
        $expenses = Expense::getTotalExpensesInFinancial($start_date,$end_date);
        $wages = PayrollRecord::getTotalBasicSalary($start_date,$end_date) ?? 0;
        $financial_charges = FinancialCharge::getTotalFinancialCharge($start_date,$end_date,null) ?? 0;
        return $gross - $expenses - $nssf - $sdl - $wages - $financial_charges;
    }
    public static function getMainlandTaxation($start_date,$end_date){
        $Profit_from_Operating_Activities_Before_Taxation_current = self::ProfitFromOperatingActivitiesBeforeTaxation($start_date,$end_date);
//        $employee_percentage = 0;
//        $additional_amount = 0;
//        $maximum_amount = 0;
//        if($Profit_from_Operating_Activities_Before_Taxation_current >= 0 && $Profit_from_Operating_Activities_Before_Taxation_current < 3240000){
//            $employee_percentage = 0;
//            $additional_amount = 0;
//            $maximum_amount = 3240000;
//        }elseif($Profit_from_Operating_Activities_Before_Taxation_current >= 3240000 && $Profit_from_Operating_Activities_Before_Taxation_current < 6240000){
//            $employee_percentage = 0.09;
//            $additional_amount = 0;
//            $maximum_amount = 6240000;
//        }elseif($Profit_from_Operating_Activities_Before_Taxation_current >= 6240000 && $Profit_from_Operating_Activities_Before_Taxation_current < 9120000){
//            $employee_percentage = 0.2;
//            $additional_amount = 270000;
//            $maximum_amount = 9120000;
//        }elseif($Profit_from_Operating_Activities_Before_Taxation_current >= 9120000 && $Profit_from_Operating_Activities_Before_Taxation_current < 12000000){
//            $employee_percentage = 0.25;
//            $additional_amount = 846000;
//            $maximum_amount = 120000000;
//        }elseif($Profit_from_Operating_Activities_Before_Taxation_current >= 12000000){
//            $employee_percentage = 0.30;
//            $additional_amount = 1566000;
//            $maximum_amount = 12000000;
//        }
//        return $Profit_from_Operating_Activities_Before_Taxation_current*0.30;
//        return ($additional_amount + $employee_percentage* ($maximum_amount - $Profit_from_Operating_Activities_Before_Taxation_current));
        if($Profit_from_Operating_Activities_Before_Taxation_current < 0){
            $tax = 0;
        }else{
            $tax = $Profit_from_Operating_Activities_Before_Taxation_current * 0.3;
        }
        return $tax;
    }

    public static function Profit_From_Operating_Activities_After_Taxation($start_date,$end_date){
        $before_taxation = self::ProfitFromOperatingActivitiesBeforeTaxation($start_date,$end_date);
        $taxation = self::getMainlandTaxation($start_date,$end_date);
        return $before_taxation-$taxation;
    }
}
