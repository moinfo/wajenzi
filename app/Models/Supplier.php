<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Supplier extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name','supplier_type', 'phone', 'address', 'email', 'vrn', 'supplier', 'system_id', 'account_name', 'nmb_account', 'nbc_account', 'crdb_account', 'is_transferred', 'whitestar_supplier_id', 'supplier_depend_on_system', 'debit'];

    public static function getSupplierName($supplier_id)
    {
        return Supplier::where('id',$supplier_id)->get()->first()['name'];
    }

    public static function isWhitestar($supplier_id)
    {
        $whitestar = Supplier::where('id',$supplier_id)->get()->first()['supplier_depend_on_system'];
        if($whitestar == 'WHITESTAR'){
            return true;
        }else{
            return false;
        }
    }

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }
    public function transactionMovements() {
        return $this->hasMany(TransactionMovement::class);
    }
    public function system() {
        return $this->belongsTo(System::class);
    }

    public function supplierReceivings() {
        return $this->hasMany(SupplierReceiving::class);
    }

    public static function getWhitestarSuppliers()
    {

        return DB::connection('mysql6')->table('whitestar.ospos_people')
            ->select(DB::raw("ospos_people.first_name AS first_name,ospos_people.last_name AS last_name,ospos_people.person_id as local_supplier_id"))
            ->join('whitestar.ospos_suppliers','ospos_suppliers.person_id','=','ospos_people.person_id')
            ->where('ospos_suppliers.deleted', '0')
            ->get();
    }

    public static function getWhitestarSupplier($local_supplier_id)
    {
        return DB::connection('mysql6')->table('whitestar.ospos_people')
            ->select(DB::raw("ospos_people.first_name AS first_name,ospos_people.last_name AS last_name,ospos_people.person_id as local_supplier_id"))
            ->join('whitestar.ospos_suppliers','ospos_suppliers.person_id','=','ospos_people.person_id')
            ->where('ospos_suppliers.deleted', '0')
            ->where('ospos_people.person_id', $local_supplier_id)
            ->get()->first();
    }

    public static function getBongeSuppliers()
    {

        return DB::connection('mysql5')->table('bonge.ospos_people')
            ->select(DB::raw("ospos_people.first_name AS first_name,ospos_people.last_name AS last_name,ospos_people.person_id as local_supplier_id"))
            ->join('bonge.ospos_suppliers','ospos_suppliers.person_id','=','ospos_people.person_id')
            ->where('ospos_suppliers.deleted', '0')
            ->get();
    }

    public static function getBongeSupplier($local_supplier_id)
    {
        return DB::connection('mysql5')->table('bonge.ospos_people')
            ->select(DB::raw("ospos_people.first_name AS first_name,ospos_people.last_name AS last_name,ospos_people.person_id as local_supplier_id"))
            ->join('bonge.ospos_suppliers','ospos_suppliers.person_id','=','ospos_people.person_id')
            ->where('ospos_suppliers.deleted', '0')
            ->where('ospos_people.person_id', $local_supplier_id)
            ->get()->first();
    }
    public static function getBongeSuppliersWithCreditAndDebit()
    {

        return DB::connection('mysql5')->select("SELECT *,
(SELECT first_name FROM bonge.ospos_people o WHERE o.person_id = s.person_id) as firstname,
(SELECT last_name FROM bonge.ospos_people o WHERE o.person_id = s.person_id) as lastname,
(SELECT sum(quantity_purchased*item_unit_price) FROM bonge.ospos_receivings_items r,bonge.ospos_receivings x WHERE r.receiving_id = x.receiving_id AND x.supplier_id = s.person_id AND x.payment_type = 'Credit Card') as credit,
(SELECT SUM(dr) FROM bonge.ospos_debits_credits c WHERE c.client_id = s.person_id) as debit
 FROM bonge.ospos_suppliers s");
    }
    public static function getBongeSupplierWithCredit($supplier_id)
    {
        return DB::connection('mysql5')->table('bonge.ospos_receivings_items')
            ->select(DB::raw("sum(quantity_purchased*item_unit_price) AS credit"))
            ->join('bonge.ospos_receivings','ospos_receivings.receiving_id','=','ospos_receivings_items.receiving_id')
            ->where('ospos_receivings.payment_type', 'Credit Card')
            ->where('ospos_receivings.supplier_id', $supplier_id)
            ->get()->first()->credit;
    }
    public static function getWhitestarSupplierWithCredit($supplier_id)
    {
        return DB::connection('mysql6')->table('whitestar.ospos_receivings_items')
            ->select(DB::raw("sum(quantity_purchased*item_unit_price) AS credit"))
            ->join('whitestar.ospos_receivings','ospos_receivings.receiving_id','=','ospos_receivings_items.receiving_id')
            ->where('ospos_receivings.payment_type', 'Credit Card')
            ->where('ospos_receivings.supplier_id', $supplier_id)
            ->get()->first()->credit;
    }
    public static function getLemuruSupplierWithDebitWithoutTransfer($supplier_id)
    {
        $date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        return DB::connection('mysql')->table('bank_reconciliations')
            ->select(DB::raw("SUM(debit) AS debit"))
            ->where('payment_type', 'SALES')
            ->where('supplier_id', $supplier_id)
            ->where('reference', 'NOT LIKE', "%TRANSFER%")
            ->whereBetween('date', ['2010-01-01',$end_date])
            ->get()->first()->debit;
    }
    public static function getLemuruSupplierWithDebitWithTransfer($supplier_id)
    {
        $date = date('Y-m-d');
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        return DB::connection('mysql')->table('bank_reconciliations')
            ->select(DB::raw("SUM(debit) AS debit"))
            ->where('payment_type', 'SALES')
            ->where('supplier_id', $supplier_id)
            ->where('reference', 'LIKE', "%TRANSFER%")
            ->whereBetween('date', ['2010-01-01',$date])
            ->get()->first()->debit;
    }
}
