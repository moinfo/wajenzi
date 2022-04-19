<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name','supplier_type', 'phone', 'address', 'email', 'vrn', 'supplier', 'system_id', 'account_name', 'nmb_account', 'nbc_account', 'crdb_account', 'is_transferred', 'whitestar_supplier_id'];

    public static function getSupplierName($supplier_id)
    {
        return Supplier::where('id',$supplier_id)->get()->first()['name'];
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
}
