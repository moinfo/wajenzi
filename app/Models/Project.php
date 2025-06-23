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
        'project_type_id',
        'status',
        'start_date',
        'expected_end_date',
        'actual_end_date',
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


    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
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

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }
}
