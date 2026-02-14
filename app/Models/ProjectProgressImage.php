<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgressImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'uploaded_by',
        'title',
        'description',
        'file',
        'file_name',
        'taken_at',
        'construction_phase_id',
    ];

    protected $casts = [
        'taken_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function constructionPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectConstructionPhase::class, 'construction_phase_id');
    }
}
