<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'description',  'allowance_type'];

    public static function getAllowanceAmountPerType($allowance_type, $allowance_amount, $month)
    {
        if($allowance_type == 'DAILY'){

            if($month == 12){
                $actual_month = 1;
            }else{
                $actual_month = $month + 1;
            }

            $datestring = date("Y-$actual_month-01");

            // Converting string to date
            $date = strtotime($datestring);
            $years = date('Y');

            // Last date of current month.
            $lastdate = strtotime(date("Y-m-t", $date ));

            // Day of the last date
            $lastDay = date("d", $lastdate);

            $monthName = date("F", mktime(0, 0, 0, $actual_month));
            $fromdt = date('Y-m-01 ',strtotime("First Day Of  $monthName $years")) ;
            $todt = date('Y-m-d ',strtotime("Last Day of $monthName $years"));

            $num_sundays='';
            for ($i = 0; $i < ((strtotime($todt) - strtotime($fromdt)) / 86400); $i++)
            {
                if(date('l',strtotime($fromdt) + ($i * 86400)) == 'Sunday')
                {
                    $num_sundays++;
                }
            }

            return ($lastDay-$num_sundays)*$allowance_amount;
        }else{
            return $allowance_amount;
        }

    }

    public static function getAllowanceType($allowance_id)
    {
        return Allowance::where('id',$allowance_id)->get()->first()['allowance_type'];
    }

    public function allowanceSubscriptions(){
        return $this->hasMany(AllowanceSubscription::class);
    }
}
