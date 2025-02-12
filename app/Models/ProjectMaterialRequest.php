<?php
// ProjectMaterialRequest.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterialRequest extends Model
{

    use HasFactory;

    protected $table = 'project_material_requests';

    protected $fillable = [
        'project_id',
        'requester_id',
        'status',
        'approved_date'
    ];

    protected $casts = [
        'approved_date' => 'datetime'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
