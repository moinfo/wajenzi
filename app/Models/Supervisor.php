<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'details'];

    public function grosses() {
        return $this->hasMany(Gross::class);
    }
}
