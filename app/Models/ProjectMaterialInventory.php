<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMaterialInventory extends Model
{
    use HasFactory;

    protected $table = 'project_material_inventory';

    protected $fillable = [
        'project_id',
        'material_id',
        'boq_item_id',
        'quantity',
        'quantity_used',
        'minimum_stock_level',
        'last_updated_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_used' => 'decimal:2',
        'minimum_stock_level' => 'decimal:2',
        'last_updated_at' => 'datetime'
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterial::class, 'material_id');
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProjectMaterialMovement::class, 'inventory_id');
    }

    // Calculated attributes
    public function getQuantityAvailableAttribute(): float
    {
        return max(0, $this->quantity - $this->quantity_used);
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_used / $this->quantity) * 100);
    }

    public function isLowStock(): bool
    {
        return $this->quantity_available <= $this->minimum_stock_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_available <= 0;
    }

    public function hasStock(): bool
    {
        return $this->quantity_available > 0;
    }

    // Receive materials
    public function receive(float $quantity): void
    {
        $this->increment('quantity', $quantity);
        $this->last_updated_at = now();
        $this->save();
    }

    // Issue materials
    public function issue(float $quantity): bool
    {
        if ($quantity > $this->quantity_available) {
            return false;
        }
        $this->increment('quantity_used', $quantity);
        $this->last_updated_at = now();
        $this->save();
        return true;
    }

    // Return materials
    public function returnMaterial(float $quantity): void
    {
        $this->decrement('quantity_used', min($quantity, $this->quantity_used));
        $this->last_updated_at = now();
        $this->save();
    }

    // Adjust inventory
    public function adjust(float $newQuantity, ?string $reason = null): void
    {
        $oldQuantity = $this->quantity;
        $this->quantity = $newQuantity;
        $this->last_updated_at = now();
        $this->save();

        // Create adjustment movement record
        ProjectMaterialMovement::create([
            'project_id' => $this->project_id,
            'boq_item_id' => $this->boq_item_id,
            'inventory_id' => $this->id,
            'movement_type' => 'adjustment',
            'quantity' => abs($newQuantity - $oldQuantity),
            'movement_date' => now(),
            'notes' => $reason ?? "Inventory adjustment from {$oldQuantity} to {$newQuantity}",
            'performed_by' => auth()->id(),
            'balance_after' => $this->quantity_available
        ]);
    }

    // Stock status badge
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusBadgeClassAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'in_stock' => 'success',
            default => 'secondary'
        };
    }

    public function getStockStatusLabelAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    // Scopes
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForBoqItem($query, $boqItemId)
    {
        return $query->where('boq_item_id', $boqItemId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(quantity - quantity_used) <= minimum_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity - quantity_used) <= 0');
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('(quantity - quantity_used) > 0');
    }
}
