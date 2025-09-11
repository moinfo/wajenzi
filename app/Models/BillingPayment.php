<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'document_id',
        'client_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'bank_name',
        'cheque_number',
        'transaction_id',
        'notes',
        'status',
        'received_by',
        'received_by_signature',
        'receipt_signed_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'receipt_signed_at' => 'datetime'
    ];

    public function document()
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }

    public function client()
    {
        return $this->belongsTo(BillingClient::class, 'client_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function generatePaymentNumber()
    {
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        $prefix = $settings['payment_prefix'] ?? 'PAY-';
        $year = now()->year;
        
        $lastPayment = self::where('payment_number', 'like', $prefix . $year . '-%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->payment_number, -5));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = $payment->generatePaymentNumber();
            }
            
            if (!$payment->payment_date) {
                $payment->payment_date = now();
            }
            
            // Set received_by to current user if not set
            if (!$payment->received_by && auth()->check()) {
                $payment->received_by = auth()->id();
            }
            
            // Auto-sign the receipt
            $payment->signReceipt();
        });
        
        static::created(function ($payment) {
            // Update document paid amount and status
            if ($payment->document && $payment->status === 'completed') {
                $document = $payment->document;
                $document->paid_amount = $document->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
                $document->balance_amount = $document->total_amount - $document->paid_amount;
                $document->save();
                
                $document->updatePaymentStatus();
            }
            
            // Update client balance
            if ($payment->client) {
                $payment->client->updateBalance();
            }
        });
        
        static::updated(function ($payment) {
            // Recalculate document totals
            if ($payment->document) {
                $document = $payment->document;
                $document->paid_amount = $document->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
                $document->balance_amount = $document->total_amount - $document->paid_amount;
                $document->save();
                
                $document->updatePaymentStatus();
            }
            
            // Update client balance
            if ($payment->client) {
                $payment->client->updateBalance();
            }
        });
        
        static::deleted(function ($payment) {
            // Recalculate document totals
            if ($payment->document) {
                $document = $payment->document;
                $document->paid_amount = $document->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
                $document->balance_amount = $document->total_amount - $document->paid_amount;
                $document->save();
                
                $document->updatePaymentStatus();
            }
            
            // Update client balance
            if ($payment->client) {
                $payment->client->updateBalance();
            }
        });
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Sign the payment receipt
     */
    public function signReceipt()
    {
        if (auth()->check() && auth()->user()->file && !$this->receipt_signed_at) {
            $this->received_by_signature = auth()->user()->file;
            $this->receipt_signed_at = now();
        }
    }

    /**
     * Check if receipt is signed
     */
    public function getIsReceiptSignedAttribute()
    {
        return !empty($this->received_by_signature) && !empty($this->receipt_signed_at);
    }

    /**
     * Get receiver signature
     */
    public function getReceiverSignatureAttribute()
    {
        return $this->received_by_signature;
    }
}