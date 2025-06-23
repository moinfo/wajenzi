<?php
// System Backup Model
// ProjectSystemBackup.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSystemBackup extends Model
{
    use HasFactory;

    protected $table = 'project_system_backups';

    protected $fillable = [
        'backup_date',
        'backup_path',
        'status',
        'size_in_mb',
        'created_by'
    ];

    protected $casts = [
        'backup_date' => 'datetime',
        'size_in_mb' => 'decimal:2'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
