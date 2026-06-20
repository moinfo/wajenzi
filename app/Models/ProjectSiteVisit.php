<?php
// Project Site Visit Model — drives the 6-stage Site Visit Workflow.
// ProjectSiteVisit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSiteVisit extends Model
{
    use HasFactory;

    protected $table = 'project_site_visits';

    /**
     * Ordered workflow stages. The key is stored in the `stage` column; the value
     * is the human label used by badges/progress trackers.
     */
    public const STAGES = [
        'initiation'   => 'Initiation',
        'billing'      => 'Billing & Invoice',
        'assignment'   => 'Assignment',
        'confirmation' => 'Confirmation',
        'reporting'    => 'Reporting',
        'integration'  => 'Schedule Integration',
        'completed'    => 'Completed',
    ];

    protected $fillable = [
        'reference_number',
        'stage',
        'project_id',
        'client_id',
        'inspector_id',
        'visit_date',
        'status',
        'location',
        'description',
        'phone_number',
        'findings',
        'recommendations',
        'create_by_id',
        'document_number',
        // Billing
        'invoice_amount',
        'invoice_number',
        'billed_by',
        'payment_confirmed_at',
        'payment_confirmed_by',
        // Assignment
        'architect_id',
        'site_engineer_id',
        'site_supervisor_id',
        'assigned_by',
        'assigned_at',
        // Confirmation
        'team_confirmed_at',
        'team_confirmed_by',
        // Reporting
        'report_path',
        'report_name',
        'report_notes',
        'report_uploaded_at',
        'report_uploaded_by',
        // Integration
        'schedule_activity_id',
        'schedule_attachment_id',
        'integrated_at',
        // Cancel
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'visit_date'           => 'date',
        'invoice_amount'       => 'decimal:2',
        'payment_confirmed_at' => 'datetime',
        'assigned_at'          => 'datetime',
        'team_confirmed_at'    => 'datetime',
        'report_uploaded_at'   => 'datetime',
        'integrated_at'        => 'datetime',
        'cancelled_at'         => 'datetime',
    ];

    /**
     * Next unique reference number, e.g. SV-2026-0001.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'SV-' . date('Y') . '-';

        $last = static::where('reference_number', 'like', "{$prefix}%")
            ->orderBy('reference_number', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->reference_number, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Human label for the current stage.
     */
    public function stageLabel(): string
    {
        if ($this->stage === 'cancelled') {
            return 'Cancelled';
        }

        return self::STAGES[$this->stage] ?? ucfirst((string) $this->stage);
    }

    /**
     * 1-based position of the current stage in the workflow (0 for cancelled/unknown).
     */
    public function stageIndex(): int
    {
        $keys = array_keys(self::STAGES);
        $pos  = array_search($this->stage, $keys, true);

        return $pos === false ? 0 : $pos + 1;
    }

    public function stageCount(): int
    {
        return count(self::STAGES);
    }

    /**
     * Is the given user one of the assigned field team (architect/engineer/supervisor)?
     */
    public function isOnTeam(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return in_array($userId, array_filter([
            $this->architect_id,
            $this->site_engineer_id,
            $this->site_supervisor_id,
        ]), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->stage, ['completed', 'cancelled'], true);
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function architect(): BelongsTo
    {
        return $this->belongsTo(User::class, 'architect_id');
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function siteSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_supervisor_id');
    }

    public function billedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billed_by');
    }

    public function paymentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function teamConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_confirmed_by');
    }

    public function reportUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'report_uploaded_by');
    }

    public function scheduleActivity(): BelongsTo
    {
        return $this->belongsTo(ProjectScheduleActivity::class, 'schedule_activity_id');
    }
}
