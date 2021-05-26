<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    public $fillable = ['id', 'expenses_sub_category_id', 'amount', 'description', 'date', 'file', 'status'];

    public function expensesCategory(){
        return $this->belongsTo(ExpensesCategory::class);
    }
    public function expensesSubCategory(){
        return $this->belongsTo(ExpensesSubCategory::class);
    }
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }

}
