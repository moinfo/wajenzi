<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCharge extends Model
{
    use HasFactory;
    public $fillable = ['id', 'financial_charge_category_id', 'amount', 'description', 'date'];

    public function financialChargeCategory(){
        return $this->belongsTo(FinancialChargeCategory::class);
    }
}
