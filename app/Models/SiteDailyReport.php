<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class SiteDailyReport extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $fillable = [
        'report_date',
        'site_id',
        'supervisor_id',
        'prepared_by',
        'progress_percentage',
        'next_steps',
        'challenges',
        'status'
    ];

    protected $casts = [
        'report_date' => 'date',
        'progress_percentage' => 'decimal:2',
    ];

    /**
     * Approvable interface methods
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->save();
        return true;
    }

    public function onSubmissionCompleted(\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'PENDING';
        $this->save();
        return true;
    }

    public function onApprovalRejected(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'REJECTED';
        $this->save();
        return true;
    }

    public function getDocumentType()
    {
        return 'site_daily_report';
    }

    public static function getApprovableType(): string
    {
        return static::class;
    }

    public function getDocumentNumber()
    {
        return 'SDR-' . $this->site->name . '-' . $this->report_date->format('Ymd');
    }

    public function getDocumentUrl()
    {
        return route('site_daily_report', ['id' => $this->id, 'document_type_id' => 16]);
    }

    /**
     * Relationships
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function workActivities()
    {
        return $this->hasMany(SiteWorkActivity::class)->orderBy('order_number');
    }

    public function materialsUsed()
    {
        return $this->hasMany(SiteMaterialUsed::class);
    }

    public function payments()
    {
        return $this->hasMany(SitePayment::class);
    }

    public function laborNeeded()
    {
        return $this->hasMany(SiteLaborNeeded::class);
    }

    /**
     * Scopes
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    public function scopeBySite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeBySupervisor($query, $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Helper methods
     */
    public function getTotalPayments()
    {
        return $this->payments()->sum('amount');
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

    public function canDelete()
    {
        return $this->status === 'DRAFT';
    }

    /**
     * Generate formatted report text
     */
    public function getFormattedReport()
    {
        // Debug: Log what data we have
        \Log::info('getFormattedReport Debug', [
            'report_id' => $this->id,
            'report_date' => $this->report_date ? $this->report_date->format('Y-m-d') : 'NULL',
            'site_id' => $this->site_id,
            'supervisor_id' => $this->supervisor_id,
            'site_exists' => $this->site !== null,
            'supervisor_exists' => $this->supervisor !== null,
            'site_name' => $this->site ? $this->site->name : 'NULL',
            'current_supervisor_exists' => $this->site && $this->site->currentSupervisor !== null,
            'current_supervisor_name' => ($this->site && $this->site->currentSupervisor) ? $this->site->currentSupervisor->name : 'NULL'
        ]);

        $report = "WAJENZI PROFESSIONAL CO LTD\n";
        $report .= "Site Daily Report\n\n";
        
        $report .= "📅 Tarehe: " . ($this->report_date ? $this->report_date->format('d/m/Y') : 'N/A') . "\n";
        $report .= "📍 Site: " . ($this->site ? $this->site->name : 'N/A') . "\n";
        
        // Get supervisor name - try direct relationship first, then site current supervisor
        $supervisorName = 'N/A';
        if ($this->supervisor) {
            $supervisorName = $this->supervisor->name;
        } elseif ($this->site && $this->site->currentSupervisor) {
            $supervisorName = $this->site->currentSupervisor->name;
        }
        $report .= "👤 Site supervisor: " . $supervisorName . "\n\n";
        
        // Work activities
        if ($this->workActivities->count() > 0) {
            $report .= "🛠️ Kazi\n";
            foreach ($this->workActivities as $index => $activity) {
                $report .= ($index + 1) . ". " . $activity->work_description . "\n";
            }
            $report .= "\n";
        }
        
        // Progress
        $report .= "📊 Maendeleo:\n";
        $report .= $this->progress_percentage . "%\n\n";
        
        // Materials
        if ($this->materialsUsed->count() > 0) {
            $report .= "📦 Vifaa:\n";
            foreach ($this->materialsUsed as $material) {
                $report .= "- " . $material->material_name;
                if ($material->quantity) {
                    $report .= " - " . $material->quantity;
                    if ($material->unit) {
                        $report .= " " . $material->unit;
                    }
                }
                $report .= "\n";
            }
            $report .= "\n";
        }
        
        // Payments
        if ($this->payments->count() > 0) {
            $report .= "💰 Malipo:\n";
            foreach ($this->payments as $payment) {
                $report .= "- " . $payment->payment_description . " " . number_format($payment->amount) . " TSH";
                if ($payment->payment_to) {
                    $report .= " @" . $payment->payment_to;
                }
                $report .= "\n";
            }
            $report .= "\n";
        }
        
        // Labor needed
        if ($this->laborNeeded->count() > 0) {
            $report .= "🧑🏾‍🔧 Labor Needed:\n";
            foreach ($this->laborNeeded as $labor) {
                $report .= "- " . $labor->labor_type;
                if ($labor->description) {
                    $report .= " (" . $labor->description . ")";
                }
                $report .= "\n";
            }
            $report .= "\n";
        }
        
        // Challenges
        if ($this->challenges) {
            $report .= "⚠️ Changamoto:\n";
            $report .= $this->challenges . "\n\n";
        }
        
        // Next steps
        if ($this->next_steps) {
            $report .= "➡️ Hatua zinazofuata:\n";
            $report .= $this->next_steps . "\n";
        }
        
        return $report;
    }
}