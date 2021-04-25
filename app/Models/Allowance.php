<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'description'];

    public function allowanceSubscriptions(){
        return $this->hasMany(AllowanceSubscription::class);
    }
}
