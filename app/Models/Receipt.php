<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TANESCOReceiptTrait;
class Receipt extends Model
{
    use HasFactory;
    use TANESCOReceiptTrait;
    public $timestamps = false;
    protected $fillable = [
        'company_name', 'p_o_box', 'mobile', 'tin', 'vrn', 'serial_no', 'uin',
        'tax_office', 'customer_name', 'customer_id_type', 'customer_id',
        'customer_mobile', 'receipt_number', 'receipt_z_number', 'receipt_date',
        'receipt_time', 'receipt_verification_code', 'receipt_total_excl_of_tax',
        'receipt_total_tax', 'receipt_total_incl_of_tax', 'receipt_total_discount',
        'kwh_charge', 'kva_charge', 'service_charge', 'interest_amount',
        'receipt_rea', 'receipt_ewura', 'receipt_property_tax', 'tax_rate',
        'is_tanesco', 'date'
    ];

    public static function isExist($receipt_verification_code)
    {
        $receipt = Receipt::where('receipt_verification_code',$receipt_verification_code)->get();
        if(count($receipt) > 0){
            return true;
        }else{
            return false;
        }
    }

    public function items(){
        return $this->hasMany(ReceiptItem::class);
    }


    public function adjustments()
    {
        return $this->hasMany(InvoiceAdjustment::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
