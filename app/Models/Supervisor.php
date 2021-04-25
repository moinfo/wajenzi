<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'details', 'employee_id', 'system_id'];

    public function grosses() {
        return $this->hasMany(Gross::class);
    }
    public function system() {
        return $this->belongsTo(System::class);
    }
    public function expenses(){
        return $this->hasMany(Expense::class);
    }
    public function collections(){
        return $this->hasMany(Collection::class);
    }
}
