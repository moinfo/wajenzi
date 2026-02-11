<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
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
        'requester_id',
        'approved_by',
        'status',
        'required_date',
        'purpose',
        'priority',
        'requested_date',
        'approved_date'
    ];

    protected $casts = [
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

        // Replicate Approvable trait's created callback (trait boot is overridden)
        static::created(static function ($model) {
            if (method_exists($model, 'bypassApprovalProcess') && $model->bypassApprovalProcess()) {
                return;
            }
            $model->approvalStatus()->create([
                'steps' => $model->approvalFlowSteps()->map(fn($item) => $item->toApprovalStatusArray()),
                'status' => ApprovalStatusEnum::SUBMITTED->value,
                'creator_id' => Auth::id(),
            ]);
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

    public function items(): HasMany
    {
        return $this->hasMany(ProjectMaterialRequestItem::class, 'material_request_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'material_request_id');
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(QuotationComparison::class, 'material_request_id');
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
        $this->save();

        // Update each item's BOQ quantity
        foreach ($this->items as $item) {
            $approved = $item->quantity_approved ?? $item->quantity_requested;
            $item->update(['quantity_approved' => $approved]);

            if ($item->boqItem) {
                $item->boqItem->increment('quantity_requested', $approved);
                $item->boqItem->updateProcurementStatus();
            }
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

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getItemsSummaryAttribute(): string
    {
        $count = $this->items_count;
        return $count . ' ' . ($count === 1 ? 'item' : 'items');
    }
}
