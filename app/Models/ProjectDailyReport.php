<?php
// Daily Reports Models
// ProjectDailyReport.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyReport extends Model
{
    use HasFactory;
    protected $table = 'project_daily_reports';

    protected $fillable = [
        'project_id',
        'supervisor_id',
        'report_date',
        'weather_conditions',
        'work_completed',
        'materials_used',
        'labor_hours',
        'issues_faced'
    ];

    protected $casts = [
        'report_date' => 'date',
        'labor_hours' => 'integer'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
