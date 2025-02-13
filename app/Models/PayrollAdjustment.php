<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollAdjustment extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','payroll_id','amount'];

    public function staff(){
        return $this->belongsTo(User::class);
    }
    public function payroll(){
        return $this->belongsTo(Payroll::class);
    }
    public static function getTotalAdjustmentByPayroll($payroll_id)
    {
        return  PayrollAdjustment::Where('payroll_id',$payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}
