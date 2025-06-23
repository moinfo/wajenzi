<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    public $fillable = ['invoice_no','product_id','invoice_date','due_date','amount','status','payment_mode'];

    public static function getInvoiceAmount($invoice_id)
    {
        return Invoice::where('id',$invoice_id)->get()->first()->amount ?? 0;
    }
    public static function getInvoicePayment($invoice_id)
    {
        return StatutoryInvoicePayment::select(DB::raw("SUM(amount) as total_amount"))->where('status','APPROVED')->where('invoice_id',$invoice_id)->get()->first()->total_amount ?? 0;
    }

    public static function getBalance($invoice_id)
    {
        return self::getInvoiceAmount($invoice_id) - self::getInvoicePayment($invoice_id);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
