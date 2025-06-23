<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryAccount extends Model
{
    use HasFactory;

    public $fillable = ['id', 'bank_id','account','beneficiary_id'];

    public function beneficiary(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Beneficiary::Class);
    }

    public function bank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bank::Class);
    }

}
