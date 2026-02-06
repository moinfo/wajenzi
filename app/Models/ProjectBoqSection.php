<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoqSection extends Model
{
    use HasFactory;

    protected $table = 'project_boq_sections';

    protected $fillable = [
        'boq_id',
        'parent_id',
        'name',
        'description',
        'sort_order',
    ];

    public function boq(): BelongsTo
    {
        return $this->belongsTo(ProjectBoq::class, 'boq_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqSection::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectBoqSection::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive.items');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class, 'section_id')->orderBy('sort_order');
    }

    /**
     * Recursively calculate subtotal: own items + all children subtotals.
     */
    public function getSubtotalAttribute(): float
    {
        $itemsTotal = $this->items->sum('total_price');
        $childrenTotal = $this->childrenRecursive->sum(function ($child) {
            return $child->subtotal;
        });

        return $itemsTotal + $childrenTotal;
    }
}
