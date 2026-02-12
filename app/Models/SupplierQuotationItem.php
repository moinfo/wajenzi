<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends Model
{
    protected $table = 'supplier_quotation_items';

    protected $fillable = [
        'supplier_quotation_id',
        'material_request_item_id',
        'boq_item_id',
        'description',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'supplier_quotation_id');
    }

    public function materialRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialRequestItem::class, 'material_request_item_id');
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }
}
