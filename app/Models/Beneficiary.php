<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name'];

    // Define the relationship with BeneficiaryAccount
    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BeneficiaryAccount::class, 'beneficiary_id');
    }
}
