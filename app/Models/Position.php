<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'description',
        'report_to_id',
        'status',
    ];

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'report_to_id');
    }
}
