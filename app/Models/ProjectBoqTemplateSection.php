<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoqTemplateSection extends Model
{
    use HasFactory;

    protected $table = 'project_boq_template_sections';

    protected $fillable = [
        'template_id',
        'parent_id',
        'name',
        'description',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqTemplate::class, 'template_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive.items');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBoqTemplateItem::class, 'section_id')->orderBy('sort_order');
    }

    public function getSubtotalAttribute(): float
    {
        $itemsTotal = $this->items->sum('total_price');
        $childrenTotal = $this->childrenRecursive->sum(fn($child) => $child->subtotal);
        return $itemsTotal + $childrenTotal;
    }
}
