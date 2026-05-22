<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_template_id', 'kpi_template_section_id',
        'kpa', 'responsibility', 'measure', 'target',
        'weight', 'measurement_method', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(KpiTemplateSection::class, 'kpi_template_section_id');
    }
}
