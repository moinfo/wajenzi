<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class QuotationComparison extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'quotation_comparisons';

    const MINIMUM_QUOTATIONS = 3;

    protected $fillable = [
        'comparison_number',
        'material_request_id',
        'comparison_date',
        'recommended_supplier_id',
        'selected_quotation_id',
        'recommendation_reason',
        'prepared_by',
        'approved_by',
        'approved_date',
        'status',
        'document_number'
    ];

    protected $casts = [
        'comparison_date' => 'date',
        'approved_date' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->comparison_number)) {
                $model->comparison_number = self::generateComparisonNumber();
            }
            if (empty($model->comparison_date)) {
                $model->comparison_date = now();
            }
            if (empty($model->prepared_by)) {
                $model->prepared_by = auth()->id();
            }
        });
    }

    /**
     * Generate unique comparison number: QC-YYYY-0001
     */
    public static function generateComparisonNumber(): string
    {
        $year = date('Y');
        $prefix = "QC-{$year}-";

        $lastComparison = self::where('comparison_number', 'like', "{$prefix}%")
            ->orderBy('comparison_number', 'desc')
            ->first();

        if ($lastComparison) {
            $lastNumber = (int) substr($lastComparison->comparison_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialRequest::class, 'material_request_id');
    }

    public function recommendedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'recommended_supplier_id');
    }

    public function selectedQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'selected_quotation_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'quotation_comparison_id');
    }

    // Get all quotations for this comparison via material request
    public function getQuotationsAttribute()
    {
        return $this->materialRequest?->quotations ?? collect();
    }

    // Validation
    public function hasMinimumQuotations(): bool
    {
        return $this->quotations->count() >= self::MINIMUM_QUOTATIONS;
    }

    public function getQuotationCountAttribute(): int
    {
        return $this->quotations->count();
    }

    public function getMissingQuotationsAttribute(): int
    {
        return max(0, self::MINIMUM_QUOTATIONS - $this->quotation_count);
    }

    public function canBeSubmitted(): bool
    {
        return $this->hasMinimumQuotations() &&
               $this->selected_quotation_id !== null &&
               $this->status === 'draft';
    }

    // Price analysis
    public function getLowestQuotationAttribute()
    {
        return $this->quotations
            ->where('status', '!=', 'rejected')
            ->sortBy('grand_total')
            ->first();
    }

    public function getHighestQuotationAttribute()
    {
        return $this->quotations
            ->where('status', '!=', 'rejected')
            ->sortByDesc('grand_total')
            ->first();
    }

    public function getAverageQuotationPriceAttribute(): float
    {
        $validQuotations = $this->quotations->where('status', '!=', 'rejected');
        if ($validQuotations->isEmpty()) {
            return 0;
        }
        return $validQuotations->avg('grand_total');
    }

    public function getPriceVarianceAttribute(): float
    {
        if (!$this->lowest_quotation || !$this->highest_quotation) {
            return 0;
        }
        return $this->highest_quotation->grand_total - $this->lowest_quotation->grand_total;
    }

    public function getSavingsAttribute(): float
    {
        if (!$this->selectedQuotation || !$this->highest_quotation) {
            return 0;
        }
        return $this->highest_quotation->grand_total - $this->selectedQuotation->grand_total;
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->approved_date = now();
        $this->approved_by = auth()->id();
        $this->save();

        // Mark the selected quotation as selected
        if ($this->selectedQuotation) {
            $this->selectedQuotation->markAsSelected();
        }

        // Update BOQ items to show ordered status
        if ($this->materialRequest) {
            foreach ($this->materialRequest->items as $mrItem) {
                if ($mrItem->boqItem) {
                    $mrItem->boqItem->updateProcurementStatus();
                }
            }
        }

        return true;
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

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canCreatePurchase(): bool
    {
        return $this->isApproved() && $this->selected_quotation_id !== null;
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
    public function scopeForRequest($query, $requestId)
    {
        return $query->where('material_request_id', $requestId);
    }

    public function scopeApproved($query)
    {
        return $query->whereRaw('UPPER(status) = ?', ['APPROVED']);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }
}
