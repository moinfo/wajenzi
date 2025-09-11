<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BillingReminderLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'document_id',
        'reminder_type',
        'days_before_due',
        'days_overdue',
        'recipient_email',
        'cc_emails',
        'subject',
        'message',
        'status',
        'error_message',
        'sent_by',
        'sent_at',
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
        'days_before_due' => 'integer',
        'days_overdue' => 'integer',
    ];
    
    public function document()
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }
    
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
