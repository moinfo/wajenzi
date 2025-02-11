<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectClientDocument extends Model
{
    use HasFactory;

    protected $table = 'project_client_documents';

    protected $fillable = [
        'client_id',
        'document_type',
        'file',
        'status'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }
}
