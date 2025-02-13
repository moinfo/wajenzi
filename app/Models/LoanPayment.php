<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoanPayment extends Model
{
    use HasFactory;
    public $fillable = ['id','amount','loan_id','staff_id','payment_date'];

    public function staff(){
        return $this->belongsTo(Staff::class,'staff_id');
    }

    public static function getTotalLoanPaid($staff_id){
        return LoanPayment::Select(DB::raw('SUM(amount) as amount'))->where('staff_id',$staff_id)->get()->first()['amount'] ?? 0;
    }
}
