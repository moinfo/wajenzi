<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SupplierReceiving extends Model
{
    use HasFactory;

    // Disable auto-incrementing (table has manual ID management)
    public $incrementing = false;

    protected $fillable = [
        'id',
        'receiving_number',
        'purchase_id',
        'project_id',
        'supplier_id',
        'received_by',
        'delivery_note_number',
        'amount',
        'quantity_ordered',
        'quantity_delivered',
        'condition',
        'date',
        'description',
        'file',
        'supplier_signature',
        'supervisor_signature',
        'technician_signature',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity_ordered' => 'decimal:2',
        'quantity_delivered' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Workaround for tables without auto_increment
            if (empty($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
            if (empty($model->receiving_number)) {
                $model->receiving_number = self::generateReceivingNumber();
            }
            if (empty($model->date)) {
                $model->date = now();
            }
        });
    }

    /**
     * Generate unique receiving number: SR-YYYY-0001
     */
    public static function generateReceivingNumber(): string
    {
        $year = date('Y');
        $prefix = "SR-{$year}-";

        $lastReceiving = self::where('receiving_number', 'like', "{$prefix}%")
            ->orderBy('receiving_number', 'desc')
            ->first();

        if ($lastReceiving) {
            $lastNumber = (int) substr($lastReceiving->receiving_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(MaterialInspection::class, 'supplier_receiving_id');
    }

    // Calculated attributes
    public function getQuantityVarianceAttribute(): float
    {
        if ($this->quantity_ordered === null || $this->quantity_delivered === null) {
            return 0;
        }
        return $this->quantity_delivered - $this->quantity_ordered;
    }

    public function getDeliveryPercentageAttribute(): float
    {
        if ($this->quantity_ordered <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_delivered / $this->quantity_ordered) * 100);
    }

    public function isFullDelivery(): bool
    {
        return $this->quantity_delivered >= $this->quantity_ordered;
    }

    public function isPartialDelivery(): bool
    {
        return $this->quantity_delivered > 0 && $this->quantity_delivered < $this->quantity_ordered;
    }

    public function isOverDelivery(): bool
    {
        return $this->quantity_delivered > $this->quantity_ordered;
    }

    public function hasInspection(): bool
    {
        return $this->inspections()->exists();
    }

    public function isInspected(): bool
    {
        return $this->status === 'inspected';
    }

    public function needsInspection(): bool
    {
        return in_array($this->status, ['pending', 'received']) && !$this->hasInspection();
    }

    // Condition badge helper
    public function getConditionBadgeClassAttribute(): string
    {
        return match($this->condition) {
            'good' => 'success',
            'partial_damage' => 'warning',
            'damaged' => 'danger',
            default => 'secondary'
        };
    }

    // Status badge helper
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'inspected' => 'success',
            'received' => 'info',
            'rejected' => 'danger',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }

    // Static methods for reporting
    public static function getSupplierReceivingAmount($supplier_id, $end_date)
    {
        $start_date = '2020-01-01';
        return self::where('status', 'APPROVED')
            ->whereBetween('date', [$start_date, $end_date])
            ->where('supplier_id', $supplier_id)
            ->select([DB::raw("SUM(amount) as total_amount")])
            ->groupBy('supplier_id')
            ->first()['total_amount'] ?? 0;
    }

    public static function getSystemSupplierReceivingAmount($system_id, $end_date)
    {
        $start_date = '2020-01-01';
        return self::select([DB::raw("SUM(supplier_receivings.amount) as total_amount")])
            ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
            ->join('systems', 'systems.id', '=', 'suppliers.system_id')
            ->where('supplier_receivings.status', 'APPROVED')
            ->whereBetween('supplier_receivings.date', [$start_date, $end_date])
            ->where('suppliers.system_id', $system_id)
            ->groupBy('suppliers.system_id')
            ->first()['total_amount'] ?? 0;
    }

    public static function getAllSupplierReceivingAmount($end_date)
    {
        $start_date = '2020-01-01';
        return self::where('status', 'APPROVED')
            ->whereBetween('supplier_receivings.date', [$start_date, $end_date])
            ->select([DB::raw("SUM(supplier_receivings.amount) as total_amount")])
            ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
            ->first()['total_amount'] ?? 0;
    }

    public static function getTotalSupplierReceivingPerDay($date)
    {
        return self::where('status', 'APPROVED')
            ->where('date', $date)
            ->select([DB::raw("SUM(amount) as total_amount")])
            ->groupBy('date')
            ->first()['total_amount'] ?? 0;
    }

    public static function getTotalSupplierReceivingToAllSuppliers($start_date, $end_date)
    {
        return self::where('status', 'APPROVED')
            ->whereBetween('date', [$start_date, $end_date])
            ->select([DB::raw("SUM(amount) as total_amount")])
            ->first()['total_amount'] ?? 0;
    }

    // Scopes
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForPurchase($query, $purchaseId)
    {
        return $query->where('purchase_id', $purchaseId);
    }

    public function scopePendingInspection($query)
    {
        return $query->whereIn('status', ['pending', 'received'])
            ->whereDoesntHave('inspections');
    }

    public function scopeInspected($query)
    {
        return $query->where('status', 'inspected');
    }
}
