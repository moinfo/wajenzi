<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ProjectMaterialRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    // Disable auto-incrementing (table has manual ID management)
    public $incrementing = false;

    protected $table = 'project_material_requests';

    protected $fillable = [
        'request_number',
        'project_id',
        'boq_item_id',
        'construction_phase_id',
        'requester_id',
        'approved_by',
        'status',
        'quantity_requested',
        'quantity_approved',
        'unit',
        'required_date',
        'purpose',
        'priority',
        'requested_date',
        'approved_date'
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'requested_date' => 'datetime',
        'approved_date' => 'datetime',
        'required_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Workaround for tables without auto_increment
            if (empty($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
            if (empty($model->request_number)) {
                $model->request_number = self::generateRequestNumber();
            }
            if (empty($model->requested_date)) {
                $model->requested_date = now();
            }
        });
    }

    /**
     * Generate unique request number: MR-YYYY-0001
     */
    public static function generateRequestNumber(): string
    {
        $year = date('Y');
        $prefix = "MR-{$year}-";

        $lastRequest = self::where('request_number', 'like', "{$prefix}%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Alias for shared approval header partial
    public function getDocumentNumberAttribute(): ?string
    {
        return $this->request_number;
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
    }

    public function constructionPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectConstructionPhase::class, 'construction_phase_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'material_request_id');
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(QuotationComparison::class, 'material_request_id');
    }

    // Validation helpers
    public function getAvailableQuantityAttribute(): float
    {
        if (!$this->boqItem) {
            return PHP_FLOAT_MAX;
        }
        return max(0, $this->boqItem->quantity - $this->boqItem->quantity_requested);
    }

    public function validateQuantity(): bool
    {
        if (!$this->boq_item_id) {
            return true;
        }

        return $this->quantity_requested <= $this->available_quantity;
    }

    public function canBeQuoted(): bool
    {
        return in_array($this->status, ['approved', 'APPROVED']);
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->approved_date = now();
        $this->approved_by = auth()->id();
        $this->quantity_approved = $this->quantity_approved ?? $this->quantity_requested;
        $this->save();

        // Update BOQ item quantity requested
        if ($this->boqItem) {
            $this->boqItem->increment('quantity_requested', $this->quantity_approved);
            $this->boqItem->updateProcurementStatus();
        }

        return true;
    }

    // Status helpers
    public function isPending(): bool
    {
        return strtolower($this->status) === 'pending';
    }

    public function isApproved(): bool
    {
        return strtoupper($this->status) === 'APPROVED';
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            default => 'secondary'
        };
    }
}
