<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BillingDocumentEmail extends Model
{
    protected $fillable = [
        'document_id',
        'document_type', 
        'recipient_email',
        'cc_emails',
        'subject',
        'message',
        'has_attachment',
        'attachment_filename',
        'status',
        'error_message',
        'sent_by',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'has_attachment' => 'boolean'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getCcEmailsArrayAttribute()
    {
        if (is_string($this->cc_emails)) {
            return array_filter(array_map('trim', explode(',', $this->cc_emails)));
        }
        return $this->cc_emails ?: [];
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'sent' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }
}
