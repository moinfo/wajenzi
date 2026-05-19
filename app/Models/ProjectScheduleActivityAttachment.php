<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectScheduleActivityAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'path',
        'name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    public function activity()
    {
        return $this->belongsTo(ProjectScheduleActivity::class, 'activity_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
