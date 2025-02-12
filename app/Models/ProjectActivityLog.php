<?php
// Activity Logging Models
// ProjectActivityLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivityLog extends Model
{
    use HasFactory;

    protected $table = 'project_activity_logs';

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'ip_address'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
