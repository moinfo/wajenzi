<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'address', 'email', 'vrn', 'supplier', 'system_id'];

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
}
