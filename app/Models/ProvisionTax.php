<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProvisionTax extends Model
{
    use HasFactory;
    public $fillable = ['date','amount','description','file','bank_id','debit_number'];

   public static function Profit_From_Operating_Activities_After_Provision($start_date,$end_date){
        return \App\Models\ProvisionTax::Where('date','>=',$start_date)->Where('date','<=',$end_date)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public function bank(){
       return $this->belongsTo(Bank::class);
    }

}
