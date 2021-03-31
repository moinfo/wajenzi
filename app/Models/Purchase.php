<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    public function item() {
        return $this->belongsTo(Item::class);

    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);

    }
    public $fillable = ['id', 'supplier_id', 'item_id', 'tax_invoice', 'invoice_date', 'total_amount', 'amount_vat_exc', 'vat_amount'];
}
