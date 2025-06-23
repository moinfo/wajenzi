<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name'];

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }
}
