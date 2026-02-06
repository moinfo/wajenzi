<?php
// Construction Management Models
// ProjectBoq.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoq extends Model
{
    use HasFactory;

    protected $table = 'project_boqs';

    protected $fillable = [
        'project_id',
        'version',
        'type',
        'total_amount',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class, 'boq_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProjectBoqSection::class, 'boq_id')->orderBy('sort_order');
    }

    public function rootSections(): HasMany
    {
        return $this->hasMany(ProjectBoqSection::class, 'boq_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function unsectionedItems(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class, 'boq_id')
            ->whereNull('section_id')
            ->orderBy('sort_order');
    }

    /**
     * Load hierarchical data for display/export.
     */
    public function getHierarchicalData(): self
    {
        return $this->load([
            'rootSections.childrenRecursive.items',
            'rootSections.items',
            'unsectionedItems',
        ]);
    }

    /**
     * Recalculate total from all items.
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('total_price'),
        ]);
    }
}
