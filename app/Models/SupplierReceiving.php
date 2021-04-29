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
        return SupplierReceiving::WhereBetween('date',[$start_date,$end_date])->Where('supplier_id',$supplier_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supplier_id')->get()->first()['total_amount'] ?? 0;
    }
}
