<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplateSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_template_id', 'code', 'title', 'weight_total', 'sort_order', 'is_common',
    ];

    protected $casts = [
        'weight_total' => 'decimal:2',
        'is_common'    => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KpiItem::class, 'kpi_template_section_id')->orderBy('sort_order');
    }
}
