<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    public $fillable = ['id', 'name', 'description'];
    use HasFactory;
    public function supervisors(){
        return $this->hasMany(System::class);
    }
}
