<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierQuotation extends Model
{
    use HasFactory;

    protected $table = 'supplier_quotations';

    protected $fillable = [
        'quotation_number',
        'material_request_id',
        'supplier_id',
        'quotation_date',
        'valid_until',
        'delivery_time_days',
        'payment_terms',
        'unit_price',
        'quantity',
        'total_amount',
        'vat_amount',
        'file',
        'status',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'delivery_time_days' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->quotation_number)) {
                $model->quotation_number = self::generateQuotationNumber();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Generate unique quotation number: SQ-YYYY-0001
     */
    public static function generateQuotationNumber(): string
    {
        $year = date('Y');
        $prefix = "SQ-{$year}-";

        $lastQuotation = self::where('quotation_number', 'like', "{$prefix}%")
            ->orderBy('quotation_number', 'desc')
            ->first();

        if ($lastQuotation) {
            $lastNumber = (int) substr($lastQuotation->quotation_number, -4);
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class, 'supplier_quotation_id')->orderBy('sort_order');
    }

    public function comparison(): HasOne
    {
        return $this->hasOne(QuotationComparison::class, 'selected_quotation_id');
    }

    // Calculated attributes
    public function getGrandTotalAttribute(): float
    {
        return $this->total_amount + $this->vat_amount;
    }

    public function getEffectiveUnitPriceAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return $this->grand_total / $this->quantity;
    }

    public function isValid(): bool
    {
        if (!$this->valid_until) {
            return true;
        }
        return $this->valid_until->isFuture() || $this->valid_until->isToday();
    }

    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    public function isSelected(): bool
    {
        return $this->status === 'selected';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // Mark as selected
    public function markAsSelected(): void
    {
        $this->status = 'selected';
        $this->save();

        // Reject other quotations for same material request
        self::where('material_request_id', $this->material_request_id)
            ->where('id', '!=', $this->id)
            ->where('status', '!=', 'rejected')
            ->update(['status' => 'rejected']);
    }

    // Status badge helper
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'selected' => 'success',
            'rejected' => 'danger',
            'received' => 'info',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeForRequest($query, $requestId)
    {
        return $query->where('material_request_id', $requestId);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now()->toDateString());
        });
    }

    public function scopeSelected($query)
    {
        return $query->where('status', 'selected');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
}
