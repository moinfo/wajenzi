<?php
// BOQ Management Models
// ProjectBoqItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoqItem extends Model
{

    use HasFactory;

    protected $table = 'project_boq_items';

    protected $fillable = [
        'boq_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function boq(): BelongsTo
    {
        return $this->belongsTo(ProjectBoq::class, 'boq_id');
    }
}
