<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupplierReceiving extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supplier_id', 'amount', 'date', 'description', 'file'];
    public function supplier() {
        return $this->belongsTo(Supplier::class);

    }
    public static function getSupplierReceivingAmount($supplier_id,$end_date)
    {
        $start_date = '2020-01-01';
        return SupplierReceiving::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->Where('supplier_id',$supplier_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supplier_id')->get()->first()['total_amount'] ?? 0;
    }
    public static function getSystemSupplierReceivingAmount($system_id,$end_date)
    {
        $start_date = '2020-01-01';
        return SupplierReceiving::select([DB::raw("SUM(supplier_receivings.amount) as total_amount")])->join('suppliers','suppliers.id', '=' , 'supplier_receivings.supplier_id')->join('systems','systems.id', '=' , 'suppliers.system_id')->Where('supplier_receivings.status','APPROVED')->WhereBetween('supplier_receivings.date',[$start_date,$end_date])->Where('suppliers.system_id',$system_id)->groupBy('suppliers.system_id')->get()->first()['total_amount'] ?? 0;
    }
    public static function getAllSupplierReceivingAmount($end_date)
    {
        $start_date = '2020-01-01';
        return SupplierReceiving::Where('status','APPROVED')->WhereBetween('supplier_receivings.date',[$start_date,$end_date])->select([DB::raw("SUM(supplier_receivings.amount) as total_amount")])->join('suppliers','suppliers.id','=','supplier_receivings.supplier_id')->get()->first()['total_amount'] ?? 0;
    }

    static function getTotalSupplierReceivingPerDay($date){
        return \App\Models\SupplierReceiving::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }

    static function getTotalSupplierReceivingToAllSuppliers($start_date, $end_date){
        return \App\Models\SupplierReceiving::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}
