<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectServiceDesignStage extends Model
{
    use HasFactory;

    protected $table = 'project_service_design_stages';

    protected $fillable = [
        'service_design_id',
        'name',
        'stage_order',
        'status',
        'file_path',
        'file_name',
        'notes',
        'completed_at',
        'completed_by',
        'approval_status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'stage_order'  => 'integer',
    ];

    public function isApproved(): bool  { return $this->approval_status === 'approved'; }
    public function isSubmitted(): bool { return $this->approval_status === 'submitted'; }
    public function isRejected(): bool  { return $this->approval_status === 'rejected'; }

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ProjectServiceDesign::class, 'service_design_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public static function defaultStages(): array
    {
        return [
            ['name' => 'Electrical Drawings',                     'stage_order' => 1],
            ['name' => 'Fire Alarm Detection (FADS)',              'stage_order' => 2],
            ['name' => 'ICT Drawings',                            'stage_order' => 3],
            ['name' => 'HVAC Drawings',                           'stage_order' => 4],
        ];
    }
}
