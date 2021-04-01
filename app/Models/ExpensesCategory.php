<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensesCategory extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name'];

    public function expenses(){
        return $this->hasMany(Expense::class);
    }
}
