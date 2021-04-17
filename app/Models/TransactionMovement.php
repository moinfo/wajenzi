<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMovement extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supplier_id', 'amount', 'date', 'description'];
    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}
