<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoqTemplateItem extends Model
{
    use HasFactory;

    protected $table = 'project_boq_template_items';

    protected $fillable = [
        'template_id',
        'section_id',
        'description',
        'item_type',
        'specification',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqTemplate::class, 'template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqTemplateSection::class, 'section_id');
    }
}
