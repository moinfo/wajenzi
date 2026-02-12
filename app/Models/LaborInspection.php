<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class LaborInspection extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public $incrementing = false;

    protected $table = 'labor_inspections';

    protected $fillable = [
        'id',
        'inspection_number',
        'labor_contract_id',
        'payment_phase_id',
        'inspector_id',
        'inspection_date',
        'inspection_type',
        'work_quality',
        'completion_percentage',
        'scope_compliance',
        'defects_found',
        'rectification_required',
        'rectification_notes',
        'photos',
        'inspector_signature',
        'result',
        'notes',
        'status',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'completion_percentage' => 'decimal:2',
        'scope_compliance' => 'boolean',
        'rectification_required' => 'boolean',
        'photos' => 'array',
        'verified_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
            // Auto-determine result based on quality and compliance
            if ($model->work_quality === 'unacceptable' || !$model->scope_compliance) {
                $model->result = 'fail';
            } elseif ($model->work_quality === 'poor' || $model->rectification_required) {
                $model->result = 'conditional';
            }
        });
    }

    /**
     * Generate unique inspection number: LI-YYYY-0001
     */
    public static function generateInspectionNumber(): string
    {
        $year = date('Y');
        $prefix = "LI-{$year}-";

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
    public function contract(): BelongsTo
    {
        return $this->belongsTo(LaborContract::class, 'labor_contract_id');
    }

    public function paymentPhase(): BelongsTo
    {
        return $this->belongsTo(LaborPaymentPhase::class, 'payment_phase_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'approved';
        $this->verified_by = auth()->id();
        $this->verified_at = now();
        $this->save();

        // Update payment phase to 'due' if inspection passed
        if ($this->result === 'pass' && $this->paymentPhase) {
            $this->paymentPhase->markAsDue();
        }

        // Complete contract on final inspection pass
        if ($this->inspection_type === 'final' && $this->result === 'pass') {
            $this->contract->update([
                'status' => 'completed',
                'actual_end_date' => now()
            ]);
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

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // Result helpers
    public function isPassed(): bool
    {
        return $this->result === 'pass';
    }

    public function isFailed(): bool
    {
        return $this->result === 'fail';
    }

    public function isConditional(): bool
    {
        return $this->result === 'conditional';
    }

    // Calculated attributes
    public function getPhotoCountAttribute(): int
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    // Badge helpers
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'approved' => 'success',
            'verified' => 'info',
            'pending' => 'warning',
            'rejected' => 'danger',
            'draft' => 'secondary',
            default => 'secondary'
        };
    }

    public function getResultBadgeClassAttribute(): string
    {
        return match($this->result) {
            'pass' => 'success',
            'conditional' => 'warning',
            'fail' => 'danger',
            default => 'secondary'
        };
    }

    public function getQualityBadgeClassAttribute(): string
    {
        return match($this->work_quality) {
            'excellent' => 'success',
            'good' => 'info',
            'acceptable' => 'warning',
            'poor' => 'danger',
            'unacceptable' => 'danger',
            default => 'secondary'
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->inspection_type) {
            'progress' => 'info',
            'milestone' => 'warning',
            'final' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Add photos to the inspection
     */
    public function addPhotos(array $photoPaths): void
    {
        $current = $this->photos ?? [];
        $this->photos = array_merge($current, $photoPaths);
        $this->save();
    }

    // Scopes
    public function scopeForContract($query, $contractId)
    {
        return $query->where('labor_contract_id', $contractId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['pending', 'verified']);
    }

    public function scopePassed($query)
    {
        return $query->where('result', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('result', 'fail');
    }

    public function scopeFinal($query)
    {
        return $query->where('inspection_type', 'final');
    }
}
