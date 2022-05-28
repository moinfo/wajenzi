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
    public $fillable = ['id', 'supplier_id', 'item_id', 'tax_invoice', 'invoice_date', 'total_amount', 'amount_vat_exc', 'vat_amount', 'purchase_type', 'file', 'date', 'status'];

    public function getAll($start_date, $end_date, $supplier_id = null, $purchase_type = null){
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->get();
    }




    public static function getTotalPurchases($end_date, $supplier_id = null, $purchase_type = null){
        $start_date = '2020-01-01';
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.total_amount');
    }
    public static function getTotalPurchasesBySupplier($start_date,$end_date, $supplier_id = null, $purchase_type = null){
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.total_amount');
    }

    public static function getTotalPurchasesWithVAT($end_date, $supplier_id = null, $purchase_type = null, $start_date = null){
        $start_date = '2020-01-01' ?? $start_date;
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.vat_amount');
    }

    public static function getTotalPurchasesWithVATExempt($end_date, $supplier_id = null, $purchase_type = null){
        $start_date = '2020-01-01';
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.amount_vat_exc');
    }

    public static function getTotalPurchasesByDate($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(total_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }
}
