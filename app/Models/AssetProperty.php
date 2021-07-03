<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetProperty extends Model
{
    public $fillable = ['id', 'name', 'description', 'asset_id'];
    use HasFactory;

    public function asset(){
        return $this->belongsTo(Asset::class);
    }
}
