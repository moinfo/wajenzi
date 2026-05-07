<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStructuralDesignStage extends Model
{
    use HasFactory;

    protected $table = 'project_structural_design_stages';

    protected $fillable = [
        'structural_design_id',
        'name',
        'stage_order',
        'status',
        'file_path',
        'file_name',
        'notes',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'stage_order'  => 'integer',
    ];

    public function structuralDesign(): BelongsTo
    {
        return $this->belongsTo(ProjectStructuralDesign::class, 'structural_design_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public static function defaultStages(): array
    {
        return [
            ['name' => 'Structural Analysis',  'stage_order' => 1],
            ['name' => 'Foundation Design',     'stage_order' => 2],
            ['name' => 'Structural Drawings',   'stage_order' => 3],
        ];
    }
}
