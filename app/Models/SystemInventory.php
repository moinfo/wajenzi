<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemInventory extends Model
{
    use HasFactory;
    public $fillable = ['id', 'system_id', 'amount', 'date'];
    public function system() {
        return $this->belongsTo(System::class);
    }
}
