<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'boq_item_id',
        'item_id',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'quantity_received',
        'status'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity_received' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-calculate total price
            $model->total_price = $model->quantity * $model->unit_price;

            // Auto-update status based on received quantity
            $model->updateStatusFromQuantity();
        });

        static::saved(function ($model) {
            // Update BOQ item quantities when purchase item is saved
            if ($model->boq_item_id && $model->wasChanged('quantity')) {
                $model->updateBoqItemQuantities();
            }
        });
    }

    // Relationships
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Calculated attributes
    public function getQuantityPendingAttribute(): float
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_received / $this->quantity) * 100);
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    public function isPartiallyReceived(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity;
    }

    public function isPending(): bool
    {
        return $this->quantity_received == 0;
    }

    // Update status based on quantity received
    protected function updateStatusFromQuantity(): void
    {
        if ($this->quantity_received >= $this->quantity) {
            $this->status = 'complete';
        } elseif ($this->quantity_received > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
    }

    // Update BOQ item ordered quantities
    public function updateBoqItemQuantities(): void
    {
        if (!$this->boqItem) {
            return;
        }

        // Recalculate total ordered quantity for this BOQ item
        $totalOrdered = self::where('boq_item_id', $this->boq_item_id)
            ->whereHas('purchase', function ($q) {
                $q->whereRaw('UPPER(status) = ?', ['APPROVED']);
            })
            ->sum('quantity');

        $this->boqItem->quantity_ordered = $totalOrdered;
        $this->boqItem->updateProcurementStatus();
    }

    // Record receiving for this item
    public function recordReceiving(float $quantity): void
    {
        $this->quantity_received += $quantity;
        $this->save();

        // Update BOQ item received quantity
        if ($this->boqItem) {
            $this->boqItem->increment('quantity_received', $quantity);
            $this->boqItem->updateProcurementStatus();
        }
    }

    // Status badge helper
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'complete' => 'success',
            'partial' => 'warning',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeForPurchase($query, $purchaseId)
    {
        return $query->where('purchase_id', $purchaseId);
    }

    public function scopePendingDelivery($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    public function scopeComplete($query)
    {
        return $query->where('status', 'complete');
    }

    public function scopeForBoqItem($query, $boqItemId)
    {
        return $query->where('boq_item_id', $boqItemId);
    }
}
