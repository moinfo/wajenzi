<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    public $fillable = ['id', 'name', 'description'];
    use HasFactory;

    public function assetProperties(){
        return $this->hasMany(AssetProperty::class);
    }
}
