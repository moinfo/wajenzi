<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Staff extends User
{


    public static function getList() {
        return self::with('department', 'position')->get();
    }

    public static function getStaffSalary($staff_id){
        return  StaffSalary::Where('staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffAdvanceSalary($staff_id,$start_date,$end_date){
        return AdvanceSalary::Where('staff_id',$staff_id)->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }
    public static function getStaffAllowance($staff_id){
        return AllowanceSubscription::Where('staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffGrossPay($staff_id){
        return self::getStaffAllowance($staff_id) + (new Staff)->getStaffSalary($staff_id);
    }

    public static function getStaffDeduction($staff_id,$deduction_type){
        return DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation',$deduction_type)->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();
    }
}
