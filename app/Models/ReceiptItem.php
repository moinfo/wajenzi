<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'receipt_id',
        'description',
        'qty',
        'amount'
    ];

    protected $casts = [
        'qty' => 'integer',
        'amount' => 'decimal:2'
    ];

    public static function getItems($receipt_id)
    {
        return ReceiptItem::where('receipt_id',$receipt_id)->get()->Toarray();
    }

}
