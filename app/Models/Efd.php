<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Efd extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name','system_id','seller','bonge_customer_id'];

    public function sales() {
        return $this->hasMany(Sale::class);
    }
    public function system() {
        return $this->belongsTo(System::class,'system_id');
    }
    public function bonge_customer() {
        return $this->belongsTo(BongePeople::class,'bonge_customer_id','person_id');
    }

    public function transactions()
    {
        return $this->hasMany(BankReconciliation::class, 'efd_id', 'id');
    }

    public static function getSellers()
    {
        return DB::connection('mysql4')->table('leruma.ospos_supervisors')
            ->where('deleted', '0')
            ->get();
    }

    public static function getSellerName($seller)
    {
        return DB::connection('mysql4')->table('leruma.ospos_supervisors')
            ->where('deleted', '0')
            ->where('id', $seller)
            ->get()->first()->name ?? '';
    }


    public static function allWithTransactions($start_date, $end_date = null)
    {
        $start_date = $start_date ?? date('Y-m-d', 0);
        $end_date =  $end_date ?? date('Y-m-d');
        $res = self::with(["transactions" => function ($query) use($start_date, $end_date) {
            $query->where('date','>=',$start_date)->where('date','<=',$end_date)->with('supplier');
        }])->get();
        return $res;
    }

    public static function allWithTransactionsWithOfficePaymentType($start_date, $end_date = null, $payment_type = null)
    {
        $start_date = $start_date ?? date('Y-m-d', 0);
        $end_date =  $end_date ?? date('Y-m-d');
        $payment_type =  $payment_type ?? 'OFFICE';
        $res = self::with(["transactions" => function ($query) use($start_date, $end_date, $payment_type) {
            $query->where('date','>=',$start_date)->where('date','<=',$end_date)->where('payment_type','=',"$payment_type")->with('supplier');
        }])->get();
        return $res;
    }
}
