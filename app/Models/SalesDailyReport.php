<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class SalesDailyReport extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $fillable = [
        'report_date',
        'prepared_by',
        'department_id',
        'daily_summary',
        'notes_recommendations',
        'status'
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    protected $hidden = [
        'department', // Hide the old department attribute to avoid conflicts
    ];

    /**
     * Logic executed when the approval process is completed.
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

    /**
     * Logic executed when approval is rejected
     */
    public function onApprovalRejected(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'REJECTED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    /**
     * Get the document type for approval workflow
     */
    public function getDocumentType()
    {
        return 'sales_daily_report';
    }

    /**
     * Get the document number for approval workflow
     */
    public function getDocumentNumber()
    {
        return 'SDR-' . $this->id . '-' . $this->report_date->format('Ymd');
    }

    /**
     * Get the document URL for approval workflow
     */
    public function getDocumentUrl()
    {
        return route('sales_daily_report.show', ['id' => $this->id, 'document_type_id' => 14]);
    }

    /**
     * Relationships
     */
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function leadFollowups()
    {
        return $this->hasMany(SalesLeadFollowup::class);
    }

    public function salesActivities()
    {
        return $this->hasMany(SalesReportActivity::class);
    }

    public function customerAcquisitionCost()
    {
        return $this->hasOne(SalesCustomerAcquisitionCost::class);
    }

    public function clientConcerns()
    {
        return $this->hasMany(SalesClientConcern::class);
    }

    /**
     * Scopes
     */
    public function scopeByDateRange($query, $start_date, $end_date)
    {
        return $query->whereBetween('report_date', [$start_date, $end_date]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, $user_id)
    {
        return $query->where('prepared_by', $user_id);
    }

    /**
     * Helper methods
     */
    public function getTotalSalesAmount()
    {
        return $this->salesActivities()->sum('invoice_sum');
    }

    public function getPaidSalesAmount()
    {
        return $this->salesActivities()->where('status', 'paid')->sum('invoice_sum');
    }

    public function getUnpaidSalesAmount()
    {
        return $this->salesActivities()->where('status', 'not_paid')->sum('invoice_sum');
    }

    public function canEdit()
    {
        return in_array($this->status, ['DRAFT', 'REJECTED']);
    }

    public function canSubmit()
    {
        return $this->status === 'DRAFT';
    }

    public function canApprove()
    {
        return $this->status === 'PENDING';
    }
}