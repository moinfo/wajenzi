<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiReviewRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_review_id', 'kpi_item_id',
        'kpa_snapshot', 'measure_snapshot', 'target_snapshot',
        'weight_snapshot', 'section_code_snapshot',
        'actual_achieved', 'self_rate', 'supervisor_rate', 'overall_rate',
        'comment', 'sort_order',
    ];

    protected $casts = [
        'weight_snapshot'  => 'decimal:2',
        'self_rate'        => 'decimal:2',
        'supervisor_rate'  => 'decimal:2',
        'overall_rate'     => 'decimal:2',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(KpiReview::class, 'kpi_review_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(KpiItem::class, 'kpi_item_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KpiReviewAttachment::class, 'kpi_review_rating_id');
    }
}
