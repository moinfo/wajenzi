<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeductionSetting extends Model
{
    use HasFactory;
    public $fillable = ['id', 'minimum_amount', 'deduction_id', 'maximum_amount', 'employee_percentage', 'employer_percentage', 'additional_amount'];

    public static function getDeductionRange($deduction_id, $taxable_amount)
    {
        return DB::SELECT(DB::raw("SELECT * FROM deduction_settings WHERE deduction_id ='$deduction_id'  AND ({$taxable_amount} BETWEEN `minimum_amount` AND  IF(`maximum_amount` > 0, `maximum_amount`, (10000000000)))"))[0];
    }

    public static function isStaffDeductionSubscribe($deduction_id, $staff_id)
    {
        $deduction = DeductionSubscription::where('deduction_id',$deduction_id)->where('staff_id',$staff_id)->get();
        if (count($deduction)){
            return true;
        }else{
            return false;
        }
    }
    public function deduction(){
        return $this->belongsTo(Deduction::class);
    }
}
