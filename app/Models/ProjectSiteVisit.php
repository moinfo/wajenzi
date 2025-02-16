<?php
// Project Site Visit Models
// ProjectSiteVisit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSiteVisit extends Model
{
    use HasFactory;

    protected $table = 'project_site_visits';

    protected $fillable = [
        'project_id',
        'inspector_id',
        'visit_date',
        'status',
        'findings',
        'recommendations',
        'location',
        'description',
        'create_by_id',
    ];

    protected $casts = [
        'visit_date' => 'date'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
