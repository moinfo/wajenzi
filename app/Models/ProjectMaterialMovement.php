<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectMaterialMovement extends Model
{
    use HasFactory;

    protected $table = 'project_material_movements';

    protected $fillable = [
        'movement_number',
        'project_id',
        'boq_item_id',
        'inventory_id',
        'movement_type',
        'quantity',
        'unit',
        'reference_type',
        'reference_id',
        'movement_date',
        'notes',
        'location',
        'performed_by',
        'verified_by',
        'verified_at',
        'balance_after'
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'verified_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->movement_number)) {
                $model->movement_number = self::generateMovementNumber();
            }
            if (empty($model->movement_date)) {
                $model->movement_date = now();
            }
            if (empty($model->performed_by)) {
                $model->performed_by = auth()->id();
            }
        });
    }

    /**
     * Generate unique movement number: MM-YYYY-0001
     */
    public static function generateMovementNumber(): string
    {
        $year = date('Y');
        $prefix = "MM-{$year}-";

        $lastMovement = self::where('movement_number', 'like', "{$prefix}%")
            ->orderBy('movement_number', 'desc')
            ->first();

        if ($lastMovement) {
            $lastNumber = (int) substr($lastMovement->movement_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialInventory::class, 'inventory_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Polymorphic reference to source document
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Movement type helpers
    public function isReceived(): bool
    {
        return $this->movement_type === 'received';
    }

    public function isIssued(): bool
    {
        return $this->movement_type === 'issued';
    }

    public function isAdjustment(): bool
    {
        return $this->movement_type === 'adjustment';
    }

    public function isReturned(): bool
    {
        return $this->movement_type === 'returned';
    }

    public function isTransfer(): bool
    {
        return $this->movement_type === 'transfer';
    }

    // Get signed quantity (positive for received, negative for issued)
    public function getSignedQuantityAttribute(): float
    {
        return in_array($this->movement_type, ['issued', 'transfer'])
            ? -$this->quantity
            : $this->quantity;
    }

    public function isVerified(): bool
    {
        return $this->verified_by !== null;
    }

    // Verify movement
    public function verify(?int $userId = null): void
    {
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->save();
    }

    // Movement type badge helper
    public function getMovementTypeBadgeClassAttribute(): string
    {
        return match($this->movement_type) {
            'received' => 'success',
            'issued' => 'warning',
            'adjustment' => 'info',
            'returned' => 'primary',
            'transfer' => 'secondary',
            default => 'secondary'
        };
    }

    // Movement type label
    public function getMovementTypeLabelAttribute(): string
    {
        return match($this->movement_type) {
            'received' => 'Received',
            'issued' => 'Issued',
            'adjustment' => 'Adjustment',
            'returned' => 'Returned',
            'transfer' => 'Transfer',
            default => ucfirst($this->movement_type)
        };
    }

    // Create movement from inspection
    public static function createFromInspection(MaterialInspection $inspection): self
    {
        return self::create([
            'project_id' => $inspection->project_id,
            'boq_item_id' => $inspection->boq_item_id,
            'movement_type' => 'received',
            'quantity' => $inspection->quantity_accepted,
            'unit' => $inspection->boqItem?->unit,
            'reference_type' => MaterialInspection::class,
            'reference_id' => $inspection->id,
            'movement_date' => now(),
            'notes' => "Received from inspection #{$inspection->inspection_number}",
            'performed_by' => auth()->id() ?? $inspection->inspector_id
        ]);
    }

    // Create issue movement
    public static function createIssue(
        int $projectId,
        ?int $boqItemId,
        float $quantity,
        ?string $unit = null,
        ?string $notes = null,
        ?string $location = null
    ): self {
        $movement = self::create([
            'project_id' => $projectId,
            'boq_item_id' => $boqItemId,
            'movement_type' => 'issued',
            'quantity' => $quantity,
            'unit' => $unit,
            'movement_date' => now(),
            'notes' => $notes,
            'location' => $location,
            'performed_by' => auth()->id()
        ]);

        // Update inventory
        $inventory = ProjectMaterialInventory::where('project_id', $projectId)
            ->where('boq_item_id', $boqItemId)
            ->first();

        if ($inventory) {
            $inventory->increment('quantity_used', $quantity);
            $movement->inventory_id = $inventory->id;
            $movement->balance_after = $inventory->quantity - $inventory->quantity_used;
            $movement->save();
        }

        // Update BOQ item
        if ($boqItemId) {
            $boqItem = ProjectBoqItem::find($boqItemId);
            if ($boqItem) {
                $boqItem->increment('quantity_used', $quantity);
            }
        }

        return $movement;
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

    public function scopeOfType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeReceived($query)
    {
        return $query->where('movement_type', 'received');
    }

    public function scopeIssued($query)
    {
        return $query->where('movement_type', 'issued');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_by');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_by');
    }
}
