<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\BillingDocumentEmail;

class BillingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'reference_number',
        'client_id',
        'project_id',
        'parent_document_id',
        'status',
        'issue_date',
        'valid_until_date',
        'due_date',
        'payment_terms',
        'custom_payment_days',
        'currency_code',
        'exchange_rate',
        'subtotal_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'notes',
        'terms_conditions',
        'footer_text',
        'po_number',
        'sales_person',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'viewed_at',
        'paid_at'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'exchange_rate' => 'decimal:4',
        'subtotal_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2'
    ];

    public function items()
    {
        return $this->hasMany(BillingDocumentItem::class, 'document_id')->orderBy('sort_order');
    }

    public function client()
    {
        return $this->belongsTo(BillingClient::class, 'client_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class, 'document_id');
    }

    public function parentDocument()
    {
        return $this->belongsTo(BillingDocument::class, 'parent_document_id');
    }

    public function childDocuments()
    {
        return $this->hasMany(BillingDocument::class, 'parent_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emails()
    {
        return $this->hasMany(BillingDocumentEmail::class, 'document_id')->orderBy('sent_at', 'desc');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateTotals()
    {
        $subtotal = $this->items->sum('line_total');
        
        // Calculate discount
        $discountAmount = 0;
        if ($this->discount_type === 'percentage' && $this->discount_value > 0) {
            $discountAmount = ($subtotal * $this->discount_value) / 100;
        } elseif ($this->discount_type === 'fixed' && $this->discount_value > 0) {
            $discountAmount = $this->discount_value;
        }
        
        // Calculate tax (on subtotal after discount)
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $this->items->sum('tax_amount');
        
        // Calculate total
        $total = $taxableAmount + $taxAmount + $this->shipping_amount;
        
        // Update the document
        $this->update([
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'balance_amount' => $total - $this->paid_amount
        ]);
        
        return $this;
    }

    public function updatePaymentStatus()
    {
        if ($this->balance_amount <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial_paid';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
        }
        
        $this->save();
        return $this;
    }

    public function generateDocumentNumber($type = null)
    {
        $type = $type ?: $this->document_type;
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        $prefixMap = [
            'quote' => $settings['quote_prefix'] ?? 'QT-',
            'proforma' => $settings['proforma_prefix'] ?? 'PRO-',
            'invoice' => $settings['invoice_prefix'] ?? 'INV-',
            'credit_note' => $settings['credit_note_prefix'] ?? 'CN-',
            'debit_note' => $settings['debit_note_prefix'] ?? 'DN-',
            'purchase_order' => $settings['po_prefix'] ?? 'PO-',
            'delivery_note' => $settings['dn_prefix'] ?? 'DLV-',
            'receipt' => $settings['receipt_prefix'] ?? 'RCP-'
        ];
        
        $prefix = $prefixMap[$type] ?? 'DOC-';
        $year = now()->year;
        
        // Get the last document number for this type and year
        $lastDocument = self::where('document_type', $type)
            ->where('document_number', 'like', $prefix . $year . '-%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastDocument) {
            $lastNumber = intval(substr($lastDocument->document_number, -5));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function convertToInvoice()
    {
        if (!in_array($this->document_type, ['quote', 'proforma'])) {
            throw new \Exception('Only quotes and proforma invoices can be converted to invoices.');
        }
        
        $invoice = $this->replicate();
        $invoice->document_type = 'invoice';
        $invoice->document_number = $this->generateDocumentNumber('invoice');
        $invoice->parent_document_id = $this->id;
        $invoice->status = 'draft';
        $invoice->issue_date = now();
        $invoice->due_date = now()->addDays(30);
        $invoice->save();
        
        // Copy items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->document_id = $invoice->id;
            $newItem->save();
        }
        
        // Calculate totals for new invoice
        $invoice->calculateTotals();
        
        // Update original document status
        $this->update(['status' => 'accepted']);
        
        return $invoice;
    }

    public function duplicate()
    {
        $newDocument = $this->replicate();
        $newDocument->document_number = $this->generateDocumentNumber();
        $newDocument->status = 'draft';
        $newDocument->issue_date = now();
        $newDocument->save();
        
        // Copy items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->document_id = $newDocument->id;
            $newItem->save();
        }
        
        return $newDocument;
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->where('document_type', 'invoice');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'secondary',
            'pending' => 'warning',
            'sent' => 'info',
            'viewed' => 'primary',
            'accepted' => 'success',
            'rejected' => 'danger',
            'partial_paid' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'dark',
            'void' => 'dark'
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }

    public function getFormattedDocumentNumberAttribute()
    {
        return $this->document_number;
    }

    public function getIsEditableAttribute()
    {
        return in_array($this->status, ['draft', 'pending', 'sent', 'viewed']);
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'overdue' || 
               ($this->due_date && $this->due_date->isPast() && !$this->is_paid);
    }
}