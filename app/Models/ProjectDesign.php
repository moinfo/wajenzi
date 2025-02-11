<?php
// Design Management Models
// ProjectDesign.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDesign extends Model
{
    use HasFactory;

    protected $table = 'project_designs';

    protected $fillable = [
        'project_id',
        'designer_id',
        'version',
        'design_type',
        'file_path',
        'status',
        'client_feedback'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }
}
