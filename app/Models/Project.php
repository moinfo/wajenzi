<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Project extends Model implements ApprovableModel
{
    use HasFactory,Approvable;


    protected $table = 'projects';

    protected $fillable = [
        'client_id',
        'document_number',
        'project_name',
        'description',
        'project_type_id',
        'service_type_id',
        'status',
        'priority',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'contract_value',
        'salesperson_id',
        'project_manager_id',
        'file',
        'create_by_id',
    ];

    /**
     * Logic executed when the approval process is completed.
     *
     * This method handles the state transitions based on your application's status values:
     * 'CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {

        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }



    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }
    public function projectType(){
        return $this->belongsTo(ProjectType::class,'project_type_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }


    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'contract_value' => 'decimal:2',
    ];

    /**
     * Alias for project_name to allow $project->name access
     */
    public function getNameAttribute(): ?string
    {
        return $this->project_name;
    }

    /**
     * Get planned duration in days
     */
    public function getPlannedDurationAttribute(): ?int
    {
        if (!$this->start_date || !$this->expected_end_date) {
            return null;
        }
        return (int) $this->start_date->diffInDays($this->expected_end_date);
    }

    /**
     * Get actual duration in days (uses today if project not completed)
     */
    public function getActualDurationAttribute(): ?int
    {
        if (!$this->start_date) {
            return null;
        }
        $endDate = $this->actual_end_date ?? now();
        return (int) $this->start_date->diffInDays($endDate);
    }

    /**
     * Get delay in days (negative means ahead of schedule)
     */
    public function getDelayAttribute(): ?int
    {
        if ($this->planned_duration === null) {
            return null;
        }
        return (int) ($this->actual_duration - $this->planned_duration);
    }

    /**
     * Check if project is delayed
     */
    public function isDelayed(): bool
    {
        return ($this->delay ?? 0) > 0;
    }

    /**
     * Get formatted contract value
     */
    public function getFormattedContractValueAttribute(): string
    {
        return 'TZS ' . number_format($this->contract_value ?? 0, 2);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    /**
     * Get leads linked to this project
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'project_id');
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(ProjectSiteVisit::class);
    }

    public function projectDesigns(): HasMany
    {
        return $this->hasMany(ProjectDesign::class);
    }

    public function boqs(): HasMany
    {
        return $this->hasMany(ProjectBoq::class);
    }

    public function constructionPhases(): HasMany
    {
        return $this->hasMany(ProjectConstructionPhase::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(ProjectDailyReport::class);
    }

    public function materialInventory(): HasMany
    {
        return $this->hasMany(ProjectMaterialInventory::class);
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(ProjectMaterialRequest::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ProjectInvoice::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingDocument::class)
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled', 'void']);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function progressImages(): HasMany
    {
        return $this->hasMany(ProjectProgressImage::class);
    }
}
