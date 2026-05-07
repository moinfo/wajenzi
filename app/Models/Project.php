<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Project extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected static function boot(): void
    {
        parent::boot();

        // Approvable::boot() is suppressed because this model defines boot().
        // Re-register the created hook so approval_statuses records are created.
        static::created(static function (Project $model) {
            if (!$model->bypassApprovalProcess()) {
                $model->approvalStatus()->create([
                    'steps'      => $model->approvalFlowSteps()->map(fn($s) => $s->toApprovalStatusArray()),
                    'status'     => 'Created',
                    'creator_id' => \Illuminate\Support\Facades\Auth::id(),
                ]);
            }
        });

        static::deleting(function (Project $project) {
            $id = $project->id;

            // ── Procurement chain (deepest children first) ──────────────────
            // labor_contracts → labor_requests
            DB::table('labor_contracts')->where('project_id', $id)->delete();
            DB::table('labor_requests')->where('project_id', $id)->delete();

            // material_inspections reference supplier_receivings; delete before receivings
            DB::table('material_inspections')->where('project_id', $id)->delete();

            $purchaseIds = DB::table('purchases')->where('project_id', $id)->pluck('id');
            if ($purchaseIds->isNotEmpty()) {
                // purchase_items cascade from purchase_id, but delete explicitly to be safe
                DB::table('purchase_items')->whereIn('purchase_id', $purchaseIds)->delete();
                DB::table('supplier_receivings')->whereIn('purchase_id', $purchaseIds)->delete();
                DB::table('purchases')->whereIn('id', $purchaseIds)->delete();
            }

            // ── Material requests chain ──────────────────────────────────────
            $requestIds = DB::table('project_material_requests')->where('project_id', $id)->pluck('id');
            if ($requestIds->isNotEmpty()) {
                DB::table('project_material_request_items')->whereIn('material_request_id', $requestIds)->delete();
                $quotationIds = DB::table('supplier_quotations')->whereIn('material_request_id', $requestIds)->pluck('id');
                if ($quotationIds->isNotEmpty()) {
                    DB::table('supplier_quotation_items')->whereIn('supplier_quotation_id', $quotationIds)->delete();
                }
                DB::table('supplier_quotations')->whereIn('material_request_id', $requestIds)->delete();
                DB::table('quotation_comparisons')->whereIn('material_request_id', $requestIds)->delete();
            }
            DB::table('project_material_requests')->where('project_id', $id)->delete();

            // ── BOQ chain ────────────────────────────────────────────────────
            $boqIds = DB::table('project_boqs')->where('project_id', $id)->pluck('id');
            if ($boqIds->isNotEmpty()) {
                DB::table('project_boq_items')->whereIn('boq_id', $boqIds)->delete();
                DB::table('project_boq_sections')->whereIn('boq_id', $boqIds)->delete();
            }
            DB::table('project_boqs')->where('project_id', $id)->delete();

            // ── Other project children ───────────────────────────────────────
            DB::table('project_material_inventory')->where('project_id', $id)->delete();
            DB::table('project_material_movements')->where('project_id', $id)->delete();
            DB::table('project_expenses')->where('project_id', $id)->delete();
            DB::table('project_invoices')->where('project_id', $id)->delete();
            DB::table('project_construction_phases')->where('project_id', $id)->delete();
            DB::table('project_daily_reports')->where('project_id', $id)->delete();
            DB::table('project_designs')->where('project_id', $id)->delete();
            DB::table('project_documents')->where('project_id', $id)->delete();
            DB::table('project_progress_images')->where('project_id', $id)->delete();
            DB::table('project_site_visits')->where('project_id', $id)->delete();
            DB::table('users_permissions')->where('project_id', $id)->delete();
            DB::table('imprest_requests')->where('project_id', $id)->delete();

            // ── Billing documents ────────────────────────────────────────────
            $billingDocIds = DB::table('billing_documents')->where('project_id', $id)->pluck('id');
            if ($billingDocIds->isNotEmpty()) {
                DB::table('billing_payments')->whereIn('document_id', $billingDocIds)->delete();
                DB::table('billing_document_items')->whereIn('document_id', $billingDocIds)->delete();
                DB::table('billing_documents')->where('project_id', $id)->delete();
            }

            // ── Nullify loose references ─────────────────────────────────────
            DB::table('leads')->where('project_id', $id)->update(['project_id' => null]);
        });
    }


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
        // Only advance from pending — do not overwrite design_phase or later workflow statuses
        if ($this->status === 'pending') {
            $this->status = 'APPROVED';
            $this->updated_at = now();
            $this->save();
        }
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

    public function structuralDesigns(): HasMany
    {
        return $this->hasMany(ProjectStructuralDesign::class);
    }

    public function approvedStructuralDesign()
    {
        return $this->hasOne(ProjectStructuralDesign::class)->where('status', 'approved');
    }
}
