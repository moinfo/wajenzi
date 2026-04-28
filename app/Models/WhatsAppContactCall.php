<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppContactCall extends Model
{
    protected $table = 'whatsapp_contact_calls';

    protected $fillable = [
        'contact_id', 'call_date', 'outcome', 'next_followup_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'call_date'          => 'date',
        'next_followup_date' => 'date',
    ];

    const OUTCOMES = [
        'answered'  => ['label' => 'Answered',           'color' => 'success'],
        'no_answer' => ['label' => 'No Answer',          'color' => 'secondary'],
        'busy'      => ['label' => 'Busy',               'color' => 'warning'],
        'voicemail' => ['label' => 'Left Voicemail',     'color' => 'info'],
        'callback'  => ['label' => 'Callback Requested', 'color' => 'primary'],
    ];

    public function contact()
    {
        return $this->belongsTo(WhatsAppContact::class, 'contact_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
