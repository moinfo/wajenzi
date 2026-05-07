<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTransferItem extends Model
{
    use HasFactory;

    protected $table = 'material_transfer_items';

    protected $fillable = [
        'material_transfer_id',
        'source_boq_item_id',
        'destination_boq_item_id',
        'description',
        'quantity',
        'unit',
        'specification',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(MaterialTransfer::class, 'material_transfer_id');
    }

    public function sourceBoqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'source_boq_item_id');
    }

    public function destinationBoqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'destination_boq_item_id');
    }
}
