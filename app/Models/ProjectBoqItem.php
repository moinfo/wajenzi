<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoqItem extends Model
{
    use HasFactory;

    protected $table = 'project_boq_items';

    protected $fillable = [
        'item_code',
        'boq_id',
        'section_id',
        'category_id',
        'description',
        'item_type',
        'specification',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'sort_order',
        'quantity_requested',
        'quantity_ordered',
        'quantity_received',
        'quantity_used',
        'procurement_status'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity_requested' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_used' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->item_code)) {
                $model->item_code = self::generateItemCode($model->boq_id);
            }
        });
    }

    /**
     * Generate item code: BOQ-{boq_id}-001
     */
    public static function generateItemCode($boqId): string
    {
        $prefix = "BOQ-{$boqId}-";

        $lastItem = self::where('boq_id', $boqId)
            ->where('item_code', 'like', "{$prefix}%")
            ->orderBy('item_code', 'desc')
            ->first();

        if ($lastItem) {
            $lastNumber = (int) substr($lastItem->item_code, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function boq(): BelongsTo
    {
        return $this->belongsTo(ProjectBoq::class, 'boq_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqSection::class, 'section_id');
    }

    public function scopeMaterials($query)
    {
        return $query->where('item_type', 'material');
    }

    public function scopeLabour($query)
    {
        return $query->where('item_type', 'labour');
    }

    public function scopeUnsectioned($query)
    {
        return $query->whereNull('section_id');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(ProjectMaterialRequest::class, 'boq_item_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'boq_item_id');
    }

    public function materialMovements(): HasMany
    {
        return $this->hasMany(ProjectMaterialMovement::class, 'boq_item_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(ProjectMaterialInventory::class, 'boq_item_id');
    }

    // Calculated attributes
    public function getQuantityRemainingAttribute(): float
    {
        return max(0, $this->quantity - $this->quantity_requested);
    }

    public function getQuantityAvailableForOrderAttribute(): float
    {
        return max(0, $this->quantity_requested - $this->quantity_ordered);
    }

    public function getQuantityPendingDeliveryAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function getQuantityInStockAttribute(): float
    {
        return max(0, $this->quantity_received - $this->quantity_used);
    }

    public function getProcurementPercentageAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_received / $this->quantity) * 100);
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->quantity_received <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_used / $this->quantity_received) * 100);
    }

    public function getBudgetUsedAttribute(): float
    {
        return $this->quantity_ordered * $this->unit_price;
    }

    public function getBudgetRemainingAttribute(): float
    {
        return $this->total_price - $this->budget_used;
    }

    // Status helpers
    public function updateProcurementStatus(): void
    {
        if ($this->quantity_received >= $this->quantity) {
            $this->procurement_status = 'complete';
        } elseif ($this->quantity_requested > 0 || $this->quantity_ordered > 0) {
            $this->procurement_status = 'in_progress';
        } else {
            $this->procurement_status = 'not_started';
        }
        $this->save();
    }

    public function isProcurementComplete(): bool
    {
        return $this->procurement_status === 'complete';
    }

    public function isInProgress(): bool
    {
        return $this->procurement_status === 'in_progress';
    }

    public function hasShortage(): bool
    {
        return $this->quantity_in_stock < 0 ||
               ($this->quantity_ordered > 0 && $this->quantity_pending_delivery > 0);
    }

    // Status badge helper
    public function getProcurementStatusBadgeClassAttribute(): string
    {
        return match($this->procurement_status) {
            'complete' => 'success',
            'in_progress' => 'warning',
            'not_started' => 'secondary',
            default => 'secondary'
        };
    }

    // Scope for filtering
    public function scopeByProject($query, $projectId)
    {
        return $query->whereHas('boq', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });
    }

    public function scopeProcurementPending($query)
    {
        return $query->whereIn('procurement_status', ['not_started', 'in_progress']);
    }

    public function scopeProcurementComplete($query)
    {
        return $query->where('procurement_status', 'complete');
    }
}
