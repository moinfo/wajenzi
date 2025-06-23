<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeductionSetting extends Model
{
    use HasFactory;
    public $fillable = ['id', 'minimum_amount', 'deduction_id', 'maximum_amount', 'employee_percentage', 'employer_percentage', 'additional_amount'];

    public static function getDeductionRange($deduction_id, $taxable_amount) {
        return DB::table('deduction_settings')
            ->where('deduction_id', $deduction_id)
            ->where(function($query) use ($taxable_amount) {
                $query->whereRaw('? BETWEEN minimum_amount AND CASE WHEN maximum_amount > 0 THEN maximum_amount ELSE 10000000000 END', [$taxable_amount]);
            })
            ->first();
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
