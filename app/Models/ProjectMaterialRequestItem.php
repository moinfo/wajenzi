<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterialRequestItem extends Model
{
    protected $table = 'project_material_request_items';

    protected $fillable = [
        'material_request_id',
        'boq_item_id',
        'quantity_requested',
        'quantity_approved',
        'unit',
        'description',
        'specification',
        'sort_order',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
    ];

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialRequest::class, 'material_request_id');
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function getAvailableQuantityAttribute(): float
    {
        if (!$this->boqItem) {
            return PHP_FLOAT_MAX;
        }
        return max(0, $this->boqItem->quantity - $this->boqItem->quantity_requested);
    }
}
