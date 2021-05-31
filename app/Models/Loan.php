<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Loan extends Model
{
    use HasFactory;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'deduction','payment_type_id'];

    public function staff(){
        return $this->belongsTo(Staff::class);
    }


    static function getTotalLoanPerDay($date){
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalLoanAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

}
