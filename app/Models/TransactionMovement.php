<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionMovement extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supplier_id', 'amount', 'date', 'description', 'file'];
    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    public static function getSupplierTransactionAmount($supplier_id,$end_date)
    {
        $start_date = '2020-01-01';
        return TransactionMovement::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->Where('supplier_id',$supplier_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supplier_id')->get()->first()['total_amount'] ?? 0;
    }
    public static function getSystemSupplierTransactionAmount($system_id,$end_date)
    {
        $start_date = '2020-01-01';
        return TransactionMovement::select([DB::raw("SUM(transaction_movements.amount) as total_amount")])->join('suppliers','suppliers.id', '=' , 'transaction_movements.supplier_id')->join('systems','systems.id', '=' , 'suppliers.system_id')->Where('transaction_movements.status','APPROVED')->WhereBetween('transaction_movements.date',[$start_date,$end_date])->Where('suppliers.system_id',$system_id)->groupBy('suppliers.system_id')->get()->first()['total_amount'] ?? 0;
    }
    public static function getAllSupplierTransactionAmount($end_date)
    {
        $start_date = '2020-01-01';
        return TransactionMovement::select([DB::raw("SUM(transaction_movements.amount) as total_amount")])->join('suppliers','suppliers.id','=','transaction_movements.supplier_id')->Where('transaction_movements.status','APPROVED')->WhereBetween('transaction_movements.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    static function getTotalTransactionPerDay($date){
        return  \App\Models\TransactionMovement::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    static function getTotalTransactionToAllSupplier($start_date, $end_date){
        return \App\Models\TransactionMovement::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}
