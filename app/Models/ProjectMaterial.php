<?php
// Resource Management Models
// ProjectMaterial.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMaterial extends Model
{
    use HasFactory;

    protected $table = 'project_materials';

    protected $fillable = [
        'name',
        'description',
        'unit',
        'current_price'
    ];

    protected $casts = [
        'current_price' => 'decimal:2'
    ];

    public function inventory(): HasMany
    {
        return $this->hasMany(ProjectMaterialInventory::class, 'material_id');
    }
}
