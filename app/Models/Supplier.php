<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class supplier extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'address', 'email', 'vrn'];

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }

    public function supplier_receivings() {
        return $this->hasMany(SupplierReceiving::class);
    }
}
