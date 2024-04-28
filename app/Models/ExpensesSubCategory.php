<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExpensesSubCategory extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'expenses_category_id', 'is_financial'];
    public function expensesCategories(){
        return $this->hasMany(ExpensesCategory::class);
    }
    public function expensesCategory(){
        return $this->belongsTo(ExpensesCategory::class);
    }


}
