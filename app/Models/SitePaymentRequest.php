<?php

namespace App\Models;

use App\Models\Concerns\CascadesApprovalRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

/**
 * Approvable header that groups a site/day's payment lines (site_paylogs) into
 * one document. Flow: Supervisor/Engineer (initiates) → Procurement (verify)
 * → MD (approve) → Finance/Accountant (record payment).
 *
 * NOTE: this model intentionally does NOT override boot(). Defining boot() would
 * shadow the Approvable trait's created callback (the gotcha worked around in
 * ProjectMaterialRequest); instead the request_number/total are set in the
 * controller before create(), and submit() is called explicitly to enter the
 * flow (which fires ProcessSubmittedEvent → notifies Procurement).
 */
class SitePaymentRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PAID     = 'PAID';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'request_number',
        'site_id',
        'project_id',
        'payment_date',
        'total_amount',
        'status',
        'payment_reference',
        'payment_slip',
        'payment_note',
        'paid_date',
        'paid_by',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'paid_date'    => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Next unique request number, e.g. PAY-2026-0001.
     */
    public static function generateRequestNumber(): string
    {
        $prefix = 'PAY-' . date('Y') . '-';

        $last = static::where('request_number', 'like', "{$prefix}%")
            ->orderBy('request_number', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->request_number, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // Alias used by the shared approval header/partials
    public function getDocumentNumberAttribute(): ?string
    {
        return $this->request_number;
    }

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SitePaylog::class, 'site_payment_request_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SitePaymentRequestFile::class);
    }

    // Scopes
    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFullyApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PAID], true);
    }

    /**
     * RingleSoft callback: fires once every step (Procurement → MD) is approved.
     * Marks the request APPROVED so Finance can record the payment.
     */
    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->save();

        return true;
    }

    /**
     * Human-readable status combining payment + approval state, for badges.
     */
    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return 'PAID';
        }

        return match ($this->approvalStatus->status ?? null) {
            'Approved'  => 'APPROVED',
            'Rejected'  => 'REJECTED',
            'Returned'  => 'RETURNED',
            'Submitted' => 'PENDING VERIFICATION',
            'Pending'   => 'IN PROGRESS',
            default     => 'DRAFT',
        };
    }

    /**
     * Bootstrap badge colour for the display status.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->displayStatus()) {
            'PAID'      => 'success',
            'APPROVED'  => 'primary',
            'REJECTED'  => 'danger',
            'RETURNED'  => 'warning',
            default     => 'info',
        };
    }
}
