<?php
// Material Management Models
// ProjectMaterialInventory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterialInventory extends Model
{
    use HasFactory;

    protected $table = 'project_material_inventory';

    protected $fillable = [
        'project_id',
        'material_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterial::class, 'material_id');
    }
}
