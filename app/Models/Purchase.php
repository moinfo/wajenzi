<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use HasFactory;

    public function item() {
        return $this->belongsTo(Item::class);

    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
    public $fillable = ['id', 'supplier_id', 'item_id', 'tax_invoice', 'invoice_date', 'total_amount', 'amount_vat_exc', 'vat_amount', 'purchase_type', 'file'];

    public function getAll($start_date, $end_date, $supplier_id = null, $purchase_type = null){
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('invoice_date','>=',$start_date)
            ->where('invoice_date','<=',$end_date);
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->get();
    }
}
