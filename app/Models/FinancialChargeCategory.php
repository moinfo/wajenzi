<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialChargeCategory extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'charge'];

    public function financialCharges(){
        return $this->hasMany(FinancialCharge::class);
    }
}
