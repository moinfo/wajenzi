<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubActivityMaterial extends Model
{
    use HasFactory;

    public $fillable = [
        'sub_activity_id',
        'boq_item_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'decimal:2'
    ];

    public function subActivity()
    {
        return $this->belongsTo(SubActivity::class);
    }

    public function boqItem()
    {
        return $this->belongsTo(BoqTemplateItem::class, 'boq_item_id');
    }
}