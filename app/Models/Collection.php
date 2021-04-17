<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supervisor_id','bank_id', 'amount', 'date', 'description'];
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }
    public function bank(){
        return $this->belongsTo(Bank::class);
    }
}
