<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;
    public $timestamps = false;

    public static function getItems($receipt_id)
    {
        return ReceiptItem::where('receipt_id',$receipt_id)->get()->Toarray();
    }

}
