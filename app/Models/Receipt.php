<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $fillable = ['date','is_expense'];

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
}
