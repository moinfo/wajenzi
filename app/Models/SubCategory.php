<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_id','name', 'description', 'billing_cycle', 'price'
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function statutoryPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StatutoryPayment::class);
    }

    public static function getSubCategoryName($sub_category_id)
    {
        return SubCategory::where('id',$sub_category_id)->get()->first()->name ?? '';
    }

}
