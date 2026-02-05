<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborPaymentPhase extends Model
{
    use HasFactory;

    protected $table = 'labor_payment_phases';

    protected $fillable = [
        'labor_contract_id',
        'phase_number',
        'phase_name',
        'description',
        'percentage',
        'amount',
        'due_date',
        'milestone_description',
        'status',
        'paid_at',
        'paid_by',
        'payment_reference',
        'notes'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime'
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(LaborContract::class, 'labor_contract_id');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(LaborInspection::class, 'payment_phase_id');
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDue(): bool
    {
        return $this->status === 'due';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'due';
    }

    public function canBePaid(): bool
    {
        return $this->status === 'approved';
    }

    // Badge helpers
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'secondary',
            'due' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'held' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Mark phase as due (typically after inspection passes)
     */
    public function markAsDue(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'due';
        return $this->save();
    }

    /**
     * Approve the payment phase
     */
    public function approve(): bool
    {
        if (!$this->isDue()) {
            return false;
        }

        $this->status = 'approved';
        return $this->save();
    }

    /**
     * Process payment for this phase
     */
    public function processPayment(string $paymentReference, ?string $notes = null): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        $this->status = 'paid';
        $this->paid_at = now();
        $this->paid_by = auth()->id();
        $this->payment_reference = $paymentReference;
        if ($notes) {
            $this->notes = $notes;
        }
        $saved = $this->save();

        // Update contract payment totals
        if ($saved) {
            $this->contract->updatePaymentTotals();
        }

        return $saved;
    }

    /**
     * Put payment on hold
     */
    public function putOnHold(?string $reason = null): bool
    {
        $this->status = 'held';
        if ($reason) {
            $this->notes = $reason;
        }
        return $this->save();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'due');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'due', 'approved', 'held']);
    }
}
