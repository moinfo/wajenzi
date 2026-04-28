<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAdCampaign extends Model
{
    protected $table = 'whatsapp_ad_campaigns';

    protected $fillable = ['name', 'start_date', 'end_date', 'budget', 'notes', 'created_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'decimal:2',
    ];

    public function contacts()
    {
        return $this->hasMany(WhatsAppContact::class, 'campaign_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCostPerLeadAttribute(): ?string
    {
        $leads = $this->contacts_count ?? $this->contacts()->count();
        if (!$leads || !$this->budget) return null;
        return number_format((float) $this->budget / $leads, 2);
    }
}
