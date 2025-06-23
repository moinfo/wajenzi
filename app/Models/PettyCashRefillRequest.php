<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashRefillRequest extends Model
{
    use HasFactory;

    protected $table = 'petty_cash_refill_requests';

    protected $fillable = ['document_number','balance','refill_amount','status','create_by_id','file','date'];

    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function getTotalRefillAmountFromBeginning(){
        return $this->where('status', 'approved')->sum('refill_amount');
    }

   public static function getCurrentBalanceBetweenPettyCashRefillRequestAndImprestRequest(){
    return self::where('status', 'approved')->sum('refill_amount') - \App\Models\ImprestRequest::where('status', 'approved')->sum('amount');
    }


}
