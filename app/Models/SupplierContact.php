<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    use HasFactory;

    public $fillable = ['id','supplier_id','bank_id','account_name','account_number'];

    public function bank(){
        return $this->belongsTo(Bank::class,'bank_id');
    }
}
