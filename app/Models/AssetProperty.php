<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetProperty extends Model
{
    public $fillable = ['id', 'name', 'description', 'asset_id', 'user_id'];
    use HasFactory;

    public function asset(){
        return $this->belongsTo(Asset::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
}
