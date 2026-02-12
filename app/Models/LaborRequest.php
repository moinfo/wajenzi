<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class LaborRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public $incrementing = false;

    protected $table = 'labor_requests';

    protected $fillable = [
        'id',
        'request_number',
        'project_id',
        'construction_phase_id',
        'artisan_id',
        'requested_by',
        'work_description',
        'work_location',
        'estimated_duration_days',
        'start_date',
        'end_date',
        'artisan_assessment',
        'materials_list',
        'materials_included',
        'proposed_amount',
        'negotiated_amount',
        'approved_amount',
        'currency',
        'payment_terms',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'materials_list' => 'array',
        'materials_included' => 'boolean',
        'proposed_amount' => 'decimal:2',
        'negotiated_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
            if (empty($model->request_number)) {
                $model->request_number = self::generateRequestNumber();
            }
            if (empty($model->requested_by)) {
                $model->requested_by = auth()->id();
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
    }

    /**
     * Generate unique request number: LR-YYYY-0001
     */
    public static function generateRequestNumber(): string
    {
        $year = date('Y');
        $prefix = "LR-{$year}-";

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

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function constructionPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectConstructionPhase::class, 'construction_phase_id');
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'artisan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(LaborContract::class, 'labor_request_id');
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'approved';
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->approved_amount = $this->approved_amount ?? $this->negotiated_amount ?? $this->proposed_amount;
        $this->save();

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
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isContracted(): bool
    {
        return $this->status === 'contracted';
    }

    public function canCreateContract(): bool
    {
        return $this->isApproved() && !$this->contract()->exists();
    }

    // Calculated attributes
    public function getFinalAmountAttribute(): float
    {
        return $this->approved_amount ?? $this->negotiated_amount ?? $this->proposed_amount ?? 0;
    }

    // Badge helpers
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'contracted' => 'info',
            'draft' => 'secondary',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailableForContract($query)
    {
        return $query->where('status', 'approved')
            ->whereDoesntHave('contract');
    }
}
