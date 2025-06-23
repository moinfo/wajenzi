<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatutoryInvoicePayment extends Model
{
    use HasFactory;
    public $fillable = ['date','amount','invoice_id','payment_mode','description','status','file', 'created_by_id'];

    public static function getPaidAmountByDate($product_amount, $billing_cycle, $start_date_invoice, $end_date_invoice, string $start_date)
    {
        if($billing_cycle == 0){
            $billing_cycle_name = 'One Time';
            $amount_per_monthly = $product_amount;
            $total_cost = $product_amount*1;
        } elseif($billing_cycle == 12){
            $billing_cycle_name = 'Annually';
            $total_cost = $product_amount*1;
            $amount_per_monthly = $total_cost/12;
        }elseif($billing_cycle == 3){
            $billing_cycle_name = 'Quarterly';
            $total_cost = $product_amount*3;
            $amount_per_monthly = $total_cost/12;
        }elseif($billing_cycle == 6){
            $billing_cycle_name = 'Semi-Annually';
            $total_cost = $product_amount*2;
            $amount_per_monthly = $total_cost/12;
        }elseif($billing_cycle == 1){
            $billing_cycle_name = 'Monthly';
            $total_cost = $product_amount*12;
            $amount_per_monthly = $total_cost/12;

        }else{
            $billing_cycle_name = 'Nothing';
        }

        $check = \App\Classes\Utility::check_in_range($start_date_invoice,$end_date_invoice,$start_date);
        if($check == 1){
            $color = 'text-success';
            $icon = 'fa fa-check';
            return $amount_per_monthly;
        }else{
            $color = 'text-danger';
            $icon = 'fa fa-times';
            return 0;
        }
    }

    public function invoice(){
        return $this->belongsTo(Invoice::class);
    }

    public function user(){
        return $this->belongsTo(User::class,'created_by_id','id');
    }
}
