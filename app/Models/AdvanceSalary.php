<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceSalary extends Model
{
    use HasFactory;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'description'];

    public function staff(){
        return $this->belongsTo(Staff::class);
    }
}
