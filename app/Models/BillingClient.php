<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_type',
        'client_code',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_identification_number',
        'registration_number',
        'vat_number',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_same_as_billing',
        'credit_limit',
        'payment_terms',
        'custom_payment_days',
        'preferred_currency',
        'opening_balance',
        'current_balance',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'shipping_same_as_billing' => 'boolean',
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'custom_payment_days' => 'integer'
    ];

    public function documents()
    {
        return $this->hasMany(BillingDocument::class, 'client_id');
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class, 'client_id');
    }

    public function invoices()
    {
        return $this->hasMany(BillingDocument::class, 'client_id')
            ->where('document_type', 'invoice');
    }

    public function quotes()
    {
        return $this->hasMany(BillingDocument::class, 'client_id')
            ->where('document_type', 'quote');
    }

    public function updateBalance()
    {
        $totalInvoiced = $this->documents()
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('total_amount');
        
        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $totalCreditNotes = $this->documents()
            ->where('document_type', 'credit_note')
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('total_amount');
        
        $this->current_balance = $this->opening_balance + $totalInvoiced - $totalPaid - $totalCreditNotes;
        $this->save();
        
        return $this;
    }

    public function generateClientCode()
    {
        $prefix = strtoupper(substr($this->client_type, 0, 1)); // C for customer, S for supplier
        $lastClient = self::where('client_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastClient && $lastClient->client_code) {
            $lastNumber = intval(substr($lastClient->client_code, 1));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1001;
        }
        
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function getFullBillingAddressAttribute()
    {
        $address = [];
        
        if ($this->billing_address_line1) $address[] = $this->billing_address_line1;
        if ($this->billing_address_line2) $address[] = $this->billing_address_line2;
        if ($this->billing_city) $address[] = $this->billing_city;
        if ($this->billing_state) $address[] = $this->billing_state;
        if ($this->billing_postal_code) $address[] = $this->billing_postal_code;
        if ($this->billing_country) $address[] = $this->billing_country;
        
        return implode(', ', $address);
    }

    public function getFullShippingAddressAttribute()
    {
        if ($this->shipping_same_as_billing) {
            return $this->full_billing_address;
        }
        
        $address = [];
        
        if ($this->shipping_address_line1) $address[] = $this->shipping_address_line1;
        if ($this->shipping_address_line2) $address[] = $this->shipping_address_line2;
        if ($this->shipping_city) $address[] = $this->shipping_city;
        if ($this->shipping_state) $address[] = $this->shipping_state;
        if ($this->shipping_postal_code) $address[] = $this->shipping_postal_code;
        if ($this->shipping_country) $address[] = $this->shipping_country;
        
        return implode(', ', $address);
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->documents()
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['paid', 'cancelled', 'void'])
            ->sum('balance_amount');
    }

    public function getOverdueBalanceAttribute()
    {
        return $this->documents()
            ->where('document_type', 'invoice')
            ->where('status', 'overdue')
            ->sum('balance_amount');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('client_type', ['customer', 'both']);
    }

    public function scopeSuppliers($query)
    {
        return $query->whereIn('client_type', ['supplier', 'both']);
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($client) {
            if (!$client->client_code) {
                $client->client_code = $client->generateClientCode();
            }
        });
    }
}