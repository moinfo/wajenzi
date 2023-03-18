<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialCharge extends Model
{
    use HasFactory;
    public $fillable = ['id', 'financial_charge_category_id', 'amount', 'description', 'date', 'supplier_id'];

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    public static function getTotalFinancialCharge($start_date, $end_date, $financial_charge_category_id)
    {
        $financial_charges = DB::table('financial_charges')
            ->join('financial_charge_categories', 'financial_charge_categories.id', '=', 'financial_charges.financial_charge_category_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        if($financial_charge_category_id != null){
            $financial_charges->where('financial_charge_category_id','=',$financial_charge_category_id);
        }
        return $financial_charges->sum('financial_charges.amount');
    }

    public function financialChargeCategory(){
        return $this->belongsTo(FinancialChargeCategory::class);
    }
}
