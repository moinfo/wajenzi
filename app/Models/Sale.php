<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    public $fillable = ['id', 'efd_id', 'amount', 'date'];

    public function efd(){
        return $this->belongsTo(Efd::class);
    }
}
