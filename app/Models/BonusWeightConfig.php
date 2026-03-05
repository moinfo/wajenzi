<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusWeightConfig extends Model
{
    protected $fillable = ['factor', 'weight', 'description'];

    /**
     * Get all weights as an associative array.
     */
    public static function getWeights(): array
    {
        return static::pluck('weight', 'factor')->toArray();
    }
}
