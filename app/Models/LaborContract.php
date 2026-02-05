<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborContract extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $table = 'labor_contracts';

    protected $fillable = [
        'id',
        'contract_number',
        'labor_request_id',
        'project_id',
        'artisan_id',
        'supervisor_id',
        'contract_date',
        'start_date',
        'end_date',
        'actual_end_date',
        'scope_of_work',
        'terms_conditions',
        'total_amount',
        'amount_paid',
        'balance_amount',
        'currency',
        'artisan_signature',
        'supervisor_signature',
        'contract_file',
        'status',
        'notes'
    ];

    protected $casts = [
        'contract_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
            if (empty($model->contract_number)) {
                $model->contract_number = self::generateContractNumber();
            }
            if (empty($model->contract_date)) {
                $model->contract_date = now();
            }
        });

        static::saving(function ($model) {
            // Recalculate balance
            $model->balance_amount = $model->total_amount - $model->amount_paid;
        });
    }

    /**
     * Generate unique contract number: LC-YYYY-0001
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $prefix = "LC-{$year}-";

        $lastContract = self::where('contract_number', 'like', "{$prefix}%")
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function laborRequest(): BelongsTo
    {
        return $this->belongsTo(LaborRequest::class, 'labor_request_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'artisan_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function paymentPhases(): HasMany
    {
        return $this->hasMany(LaborPaymentPhase::class, 'labor_contract_id')->orderBy('phase_number');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(LaborWorkLog::class, 'labor_contract_id')->orderBy('log_date', 'desc');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(LaborInspection::class, 'labor_contract_id')->orderBy('inspection_date', 'desc');
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public function isSigned(): bool
    {
        return !empty($this->artisan_signature) && !empty($this->supervisor_signature);
    }

    // Calculated attributes
    public function getPaymentProgressAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return ($this->amount_paid / $this->total_amount) * 100;
    }

    public function getLatestProgressAttribute(): float
    {
        $latestLog = $this->workLogs()->latest('log_date')->first();
        return $latestLog ? $latestLog->progress_percentage ?? 0 : 0;
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }
        return (int) max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->end_date || $this->isCompleted()) {
            return 0;
        }
        return (int) max(0, $this->end_date->diffInDays(now(), false));
    }

    // Badge helpers
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'on_hold' => 'warning',
            'completed' => 'info',
            'terminated' => 'danger',
            'draft' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Update payment amounts after a phase is paid
     */
    public function updatePaymentTotals(): void
    {
        $this->amount_paid = $this->paymentPhases()
            ->where('status', 'paid')
            ->sum('amount');
        $this->balance_amount = $this->total_amount - $this->amount_paid;
        $this->save();
    }

    /**
     * Create default payment phases for contract
     */
    public function createDefaultPaymentPhases(): void
    {
        $defaultPhases = [
            ['phase_number' => 1, 'phase_name' => 'Mobilization', 'percentage' => 20, 'milestone_description' => 'Contract signed and work commenced'],
            ['phase_number' => 2, 'phase_name' => 'Progress', 'percentage' => 30, 'milestone_description' => '50% work completed'],
            ['phase_number' => 3, 'phase_name' => 'Substantial', 'percentage' => 30, 'milestone_description' => '90% work completed'],
            ['phase_number' => 4, 'phase_name' => 'Final', 'percentage' => 20, 'milestone_description' => 'Final inspection approved'],
        ];

        foreach ($defaultPhases as $phase) {
            $this->paymentPhases()->create([
                'phase_number' => $phase['phase_number'],
                'phase_name' => $phase['phase_name'],
                'percentage' => $phase['percentage'],
                'amount' => ($phase['percentage'] / 100) * $this->total_amount,
                'milestone_description' => $phase['milestone_description'],
                'status' => 'pending'
            ]);
        }
    }

    // Scopes
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance_amount', '>', 0);
    }
}
