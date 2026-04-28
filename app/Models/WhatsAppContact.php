<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'name', 'phone', 'stage', 'source', 'campaign_id', 'client_id',
        'next_followup_date', 'assigned_to', 'notes', 'deal_value', 'is_important', 'created_by',
    ];

    protected $casts = [
        'next_followup_date' => 'date',
        'is_important'       => 'boolean',
        'deal_value'         => 'decimal:2',
    ];

    // ── Label definitions (multi-select, colors match WhatsApp Business) ───────

    const LABELS = [
        'important'       => ['label' => 'Important',       'hex' => '#F59E0B'],
        'new_order'       => ['label' => 'New Order',       'hex' => '#F97316'],
        'new_customer'    => ['label' => 'New Customer',    'hex' => '#3B82F6'],
        'lead'            => ['label' => 'Lead',            'hex' => '#8B5CF6'],
        'follow_up'       => ['label' => 'Follow Up',       'hex' => '#10B981'],
        'paid'            => ['label' => 'Paid',            'hex' => '#EC4899'],
        'pending_payment' => ['label' => 'Pending Payment', 'hex' => '#EF4444'],
        'order_complete'  => ['label' => 'Order Complete',  'hex' => '#6B7280'],
    ];

    // ── Stage helpers ─────────────────────────────────────────────────────────

    const STAGES = [
        'lead'            => ['label' => 'Lead',            'color' => 'badge-primary'],
        'new_customer'    => ['label' => 'New Customer',    'color' => 'badge-info'],
        'new_order'       => ['label' => 'New Order',       'color' => 'badge-warning'],
        'follow_up'       => ['label' => 'Follow Up',       'color' => 'badge-secondary'],
        'pending_payment' => ['label' => 'Pending Payment', 'color' => 'badge-danger'],
        'paid'            => ['label' => 'Paid',            'color' => 'badge-success'],
        'order_complete'  => ['label' => 'Order Complete',  'color' => 'badge-success'],
    ];

    const SOURCES = [
        'whatsapp_ad' => 'WhatsApp Ad',
        'referral'    => 'Referral',
        'direct'      => 'Direct',
        'other'       => 'Other',
    ];

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage]['label'] ?? ucfirst($this->stage);
    }

    public function getStageBadgeClassAttribute(): string
    {
        return self::STAGES[$this->stage]['color'] ?? 'badge-secondary';
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst($this->source);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function campaign()
    {
        return $this->belongsTo(WhatsAppAdCampaign::class, 'campaign_id');
    }

    public function client()
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function services()
    {
        return $this->belongsToMany(
            FieldMarketingService::class,
            'whatsapp_contact_services',
            'whatsapp_contact_id',
            'field_marketing_service_id'
        );
    }

    public function syncLabels(array $labels): void
    {
        \Illuminate\Support\Facades\DB::table('whatsapp_contact_labels')
            ->where('contact_id', $this->id)->delete();
        foreach (array_unique($labels) as $lbl) {
            if (array_key_exists($lbl, self::LABELS)) {
                \Illuminate\Support\Facades\DB::table('whatsapp_contact_labels')
                    ->insert(['contact_id' => $this->id, 'label' => $lbl]);
            }
        }
    }

    public function getLabelListAttribute(): array
    {
        return \Illuminate\Support\Facades\DB::table('whatsapp_contact_labels')
            ->where('contact_id', $this->id)->pluck('label')->toArray();
    }
}
