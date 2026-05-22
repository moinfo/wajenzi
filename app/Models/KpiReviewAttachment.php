<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KpiReviewAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_review_id', 'kpi_review_rating_id',
        'file_path', 'original_name', 'mime_type', 'size_bytes', 'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(KpiReview::class, 'kpi_review_id');
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(KpiReviewRating::class, 'kpi_review_rating_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
