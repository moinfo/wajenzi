<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoqTemplate extends Model
{
    use HasFactory;

    protected $table = 'project_boq_templates';

    protected $fillable = [
        'name',
        'description',
        'type',
        'total_amount',
        'source_boq_id',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function sourceBoq(): BelongsTo
    {
        return $this->belongsTo(ProjectBoq::class, 'source_boq_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProjectBoqTemplateSection::class, 'template_id')->orderBy('sort_order');
    }

    public function rootSections(): HasMany
    {
        return $this->hasMany(ProjectBoqTemplateSection::class, 'template_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBoqTemplateItem::class, 'template_id');
    }

    public function unsectionedItems(): HasMany
    {
        return $this->hasMany(ProjectBoqTemplateItem::class, 'template_id')
            ->whereNull('section_id')
            ->orderBy('sort_order');
    }

    /**
     * Create a template from an existing BOQ by cloning its sections and items.
     */
    public static function createFromBoq(ProjectBoq $boq, string $name, ?string $description = null): self
    {
        $boq->load([
            'sections',
            'items',
        ]);

        $template = self::create([
            'name' => $name,
            'description' => $description,
            'type' => $boq->type ?? 'combined',
            'total_amount' => $boq->total_amount,
            'source_boq_id' => $boq->id,
            'created_by' => auth()->id(),
        ]);

        // Clone sections — map old IDs to new IDs for parent_id references
        $sectionMap = [];
        foreach ($boq->sections as $section) {
            $newSection = $template->sections()->create([
                'parent_id' => null, // will fix after all created
                'name' => $section->name,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
            ]);
            $sectionMap[$section->id] = $newSection->id;
        }

        // Fix parent_id references using the map
        foreach ($boq->sections as $section) {
            if ($section->parent_id && isset($sectionMap[$section->parent_id])) {
                ProjectBoqTemplateSection::where('id', $sectionMap[$section->id])
                    ->update(['parent_id' => $sectionMap[$section->parent_id]]);
            }
        }

        // Clone items — remap section_id
        foreach ($boq->items as $item) {
            $template->items()->create([
                'section_id' => $item->section_id ? ($sectionMap[$item->section_id] ?? null) : null,
                'description' => $item->description,
                'item_type' => $item->item_type ?? 'material',
                'specification' => $item->specification,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'sort_order' => $item->sort_order,
            ]);
        }

        return $template;
    }

    /**
     * Apply this template to a BOQ — clones sections and items into the target BOQ.
     */
    public function applyToBoq(ProjectBoq $boq): void
    {
        $this->load(['sections', 'items']);

        // Clone sections — map template section IDs to new BOQ section IDs
        $sectionMap = [];
        foreach ($this->sections as $section) {
            $newSection = $boq->sections()->create([
                'parent_id' => null,
                'name' => $section->name,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
            ]);
            $sectionMap[$section->id] = $newSection->id;
        }

        // Fix parent_id references
        foreach ($this->sections as $section) {
            if ($section->parent_id && isset($sectionMap[$section->parent_id])) {
                ProjectBoqSection::where('id', $sectionMap[$section->id])
                    ->update(['parent_id' => $sectionMap[$section->parent_id]]);
            }
        }

        // Clone items — remap section_id to new BOQ sections
        foreach ($this->items as $item) {
            $boq->items()->create([
                'section_id' => $item->section_id ? ($sectionMap[$item->section_id] ?? null) : null,
                'description' => $item->description,
                'item_type' => $item->item_type ?? 'material',
                'specification' => $item->specification,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'sort_order' => $item->sort_order,
            ]);
        }

        // Recalculate BOQ totals
        $boq->recalculateTotals();
    }
}
