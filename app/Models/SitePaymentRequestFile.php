<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitePaymentRequestFile extends Model
{
    protected $fillable = [
        'site_payment_request_id',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SitePaymentRequest::class, 'site_payment_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
