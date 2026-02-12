<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class MaterialInspection extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    // Disable auto-incrementing (table has manual ID management)
    public $incrementing = false;

    protected $table = 'material_inspections';

    protected $fillable = [
        'id',
        'inspection_number',
        'supplier_receiving_id',
        'project_id',
        'boq_item_id',
        'inspection_date',
        'quantity_delivered',
        'quantity_accepted',
        'quantity_rejected',
        'overall_condition',
        'overall_result',
        'rejection_reason',
        'inspection_notes',
        'criteria_checklist',
        'inspector_id',
        'verifier_id',
        'inspector_signature',
        'verifier_signature',
        'stock_updated',
        'stock_updated_at',
        'status',
        'document_number'
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'quantity_delivered' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'criteria_checklist' => 'array',
        'stock_updated' => 'boolean',
        'stock_updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Workaround for tables without auto_increment
            if (empty($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
            if (empty($model->inspection_number)) {
                $model->inspection_number = self::generateInspectionNumber();
            }
            if (empty($model->inspection_date)) {
                $model->inspection_date = now();
            }
            if (empty($model->inspector_id)) {
                $model->inspector_id = auth()->id();
            }
        });

        // Re-register the Approvable trait's created hook since overriding boot() prevents it from running
        static::created(static function ($model) {
            $model->approvalStatus()->create([
                'steps' => $model->approvalFlowSteps()->map(fn($item) => $item->toApprovalStatusArray()),
                'status' => ApprovalStatusEnum::CREATED->value,
                'creator_id' => auth()->id(),
            ]);
        });

        static::saving(function ($model) {
            // Ensure quantities are consistent
            $model->quantity_rejected = $model->quantity_delivered - $model->quantity_accepted;

            // Auto-determine result based on acceptance
            if ($model->quantity_accepted == 0) {
                $model->overall_result = 'fail';
            } elseif ($model->quantity_rejected > 0) {
                $model->overall_result = 'conditional';
            } else {
                $model->overall_result = 'pass';
            }
        });
    }

    /**
     * Generate unique inspection number: MI-YYYY-0001
     */
    public static function generateInspectionNumber(): string
    {
        $year = date('Y');
        $prefix = "MI-{$year}-";

        $lastInspection = self::where('inspection_number', 'like', "{$prefix}%")
            ->orderBy('inspection_number', 'desc')
            ->first();

        if ($lastInspection) {
            $lastNumber = (int) substr($lastInspection->inspection_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function supplierReceiving(): BelongsTo
    {
        return $this->belongsTo(SupplierReceiving::class, 'supplier_receiving_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    // Calculated attributes
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->quantity_delivered <= 0) {
            return 0;
        }
        return ($this->quantity_accepted / $this->quantity_delivered) * 100;
    }

    public function getRejectionRateAttribute(): float
    {
        return 100 - $this->acceptance_rate;
    }

    public function isPassed(): bool
    {
        return $this->overall_result === 'pass';
    }

    public function isFailed(): bool
    {
        return $this->overall_result === 'fail';
    }

    public function isConditional(): bool
    {
        return $this->overall_result === 'conditional';
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->verifier_id = auth()->id();
        $this->save();

        // Update stock if not already updated
        if (!$this->stock_updated && $this->quantity_accepted > 0) {
            $this->updateStock();
        }

        // Update supplier receiving status
        if ($this->supplierReceiving) {
            $this->supplierReceiving->status = 'inspected';
            $this->supplierReceiving->save();
        }

        return true;
    }

    /**
     * Update inventory stock with accepted materials
     */
    public function updateStock(): void
    {
        if ($this->stock_updated || $this->quantity_accepted <= 0) {
            return;
        }

        DB::transaction(function () {
            // Get or create a valid material (required by FK constraint)
            $material = \App\Models\ProjectMaterial::first();
            if (!$material) {
                $material = \App\Models\ProjectMaterial::create([
                    'name' => 'General Material',
                    'description' => 'Default material for procurement',
                    'unit' => 'pcs',
                    'current_price' => 0,
                ]);
            }

            // Find or create inventory record
            $inventory = ProjectMaterialInventory::firstOrCreate(
                [
                    'project_id' => $this->project_id,
                    'boq_item_id' => $this->boq_item_id,
                ],
                [
                    'material_id' => $material->id,
                    'quantity' => 0,
                    'quantity_used' => 0
                ]
            );

            // Update inventory quantity
            $inventory->increment('quantity', $this->quantity_accepted);
            $inventory->last_updated_at = now();
            $inventory->save();

            // Create material movement record
            ProjectMaterialMovement::create([
                'movement_number' => ProjectMaterialMovement::generateMovementNumber(),
                'project_id' => $this->project_id,
                'boq_item_id' => $this->boq_item_id,
                'inventory_id' => $inventory->id,
                'movement_type' => 'received',
                'quantity' => $this->quantity_accepted,
                'unit' => $this->boqItem?->unit,
                'reference_type' => self::class,
                'reference_id' => $this->id,
                'movement_date' => now(),
                'notes' => "Materials received from inspection {$this->inspection_number}",
                'performed_by' => auth()->id() ?? $this->inspector_id,
                'balance_after' => $inventory->quantity
            ]);

            // Update BOQ item received quantity
            if ($this->boqItem) {
                $this->boqItem->increment('quantity_received', $this->quantity_accepted);
                $this->boqItem->updateProcurementStatus();
            }

            // Mark stock as updated
            $this->stock_updated = true;
            $this->stock_updated_at = now();
            $this->save();
        });
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return strtoupper($this->status) === 'APPROVED';
    }

    // Result badge helper
    public function getResultBadgeClassAttribute(): string
    {
        return match($this->overall_result) {
            'pass' => 'success',
            'conditional' => 'warning',
            'fail' => 'danger',
            default => 'secondary'
        };
    }

    // Condition badge helper
    public function getConditionBadgeClassAttribute(): string
    {
        return match($this->overall_condition) {
            'excellent' => 'success',
            'good' => 'info',
            'acceptable' => 'warning',
            'poor' => 'danger',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    // Status badge helper
    public function getStatusBadgeClassAttribute(): string
    {
        return match(strtolower($this->status)) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'draft' => 'secondary',
            default => 'secondary'
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

    public function scopeApproved($query)
    {
        return $query->whereRaw('UPPER(status) = ?', ['APPROVED']);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePassed($query)
    {
        return $query->where('overall_result', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('overall_result', 'fail');
    }
}
