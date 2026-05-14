<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStockReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'project_stock_receipt_items';

    protected $fillable = [
        'receipt_id',
        'stock_item_id',
        'description',
        'unit',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ProjectStockReceipt::class, 'receipt_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(ProjectStockItem::class, 'stock_item_id');
    }
}
