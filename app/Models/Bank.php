<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'description'];
    public function collections(){
        return $this->hasMany(Collection::class);
    }
}
