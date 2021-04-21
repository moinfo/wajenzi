<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gross extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supervisor_id', 'amount', 'date', 'description'];
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }
}
