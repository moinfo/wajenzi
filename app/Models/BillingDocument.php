<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\BillingDocumentEmail;
use App\Models\ProjectClient;

class BillingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'reference_number',
        'client_id',
        'project_id',
        'lead_id',
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
        'late_fee_amount',
        'late_fee_percentage',
        'late_fee_applied_at',
        'last_reminder_sent_at',
        'paid_amount',
        'balance_amount',
        'notes',
        'terms_conditions',
        'footer_text',
        'po_number',
        'sales_person',
        'created_by',
        'created_by_signature',
        'signed_at',
        'approved_by',
        'approved_by_signature',
        'approved_signed_at',
        'approved_at',
        'sent_at',
        'viewed_at',
        'paid_at',
        'reminder_count',
        'original_due_date',
        'rescheduled_at',
        'rescheduled_by',
        'reschedule_reason',
        'attended_at',
        'attended_by',
        'attendance_notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until_date' => 'date',
        'due_date' => 'date',
        'signed_at' => 'datetime',
        'approved_signed_at' => 'datetime',
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
        'late_fee_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_applied_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'original_due_date' => 'date',
        'rescheduled_at' => 'datetime',
        'attended_at' => 'datetime'
    ];

    public function items()
    {
        return $this->hasMany(BillingDocumentItem::class, 'document_id')->orderBy('sort_order');
    }

    public function client()
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
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

    public function reminderLogs()
    {
        return $this->hasMany(BillingReminderLog::class, 'document_id')->orderBy('sent_at', 'desc');
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

    public function convertToProforma()
    {
        if ($this->document_type !== 'quote') {
            throw new \Exception('Only quotes can be converted to proforma invoices.');
        }
        
        $proforma = $this->replicate();
        $proforma->document_type = 'proforma';
        $proforma->document_number = $this->generateDocumentNumber('proforma');
        $proforma->parent_document_id = $this->id;
        $proforma->status = 'draft';
        $proforma->issue_date = now();
        $proforma->valid_until_date = now()->addDays(30);
        $proforma->save();
        
        // Copy items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->document_id = $proforma->id;
            $newItem->save();
        }
        
        // Calculate totals for new proforma
        $proforma->calculateTotals();
        
        // Update original quote status
        $this->update(['status' => 'accepted']);
        
        return $proforma;
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

    /**
     * Boot method to automatically sign documents
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            $document->signDocument();
        });

        static::updating(function ($document) {
            // Re-sign if status changes to sent, viewed, or accepted
            if ($document->isDirty('status') && in_array($document->status, ['sent', 'viewed', 'accepted'])) {
                $document->signDocument();
            }
        });
    }

    /**
     * Sign the document with creator's signature
     */
    public function signDocument()
    {
        if (auth()->check() && auth()->user()->file && !$this->signed_at) {
            $this->created_by_signature = auth()->user()->file;
            $this->signed_at = now();
        }
    }

    /**
     * Sign the document with approver's signature
     */
    public function signAsApprover($userId = null)
    {
        $user = $userId ? \App\Models\User::find($userId) : auth()->user();
        
        if ($user && $user->file) {
            $this->approved_by_signature = $user->file;
            $this->approved_signed_at = now();
            $this->approved_by = $user->id;
            $this->save();
        }
    }

    /**
     * Get the creator's signature
     */
    public function getCreatorSignatureAttribute()
    {
        return $this->created_by_signature;
    }

    /**
     * Get the approver's signature
     */
    public function getApproverSignatureAttribute()
    {
        return $this->approved_by_signature;
    }

    /**
     * Check if document is signed by creator
     */
    public function getIsSignedAttribute()
    {
        return !empty($this->created_by_signature) && !empty($this->signed_at);
    }

    /**
     * Check if document is signed by approver
     */
    public function getIsApprovedSignedAttribute()
    {
        return !empty($this->approved_by_signature) && !empty($this->approved_signed_at);
    }

    /**
     * Generate Google Calendar URL for invoice due date
     */
    public function getGoogleCalendarUrl(): string
    {
        $title = 'Invoice Due: ' . $this->document_number;

        // Use due_date, default to 9 AM - 10 AM
        $startDate = $this->due_date->copy()->setTime(9, 0);
        $endDate = $this->due_date->copy()->setTime(10, 0);

        // Format dates for Google Calendar (YYYYMMDDTHHmmSSZ)
        $dateFormat = 'Ymd\THis\Z';
        $dates = $startDate->utc()->format($dateFormat) . '/' . $endDate->utc()->format($dateFormat);

        // Build description
        $details = [];
        $details[] = "Invoice: {$this->document_number}";
        $details[] = "Amount: TZS " . number_format($this->total_amount, 2);
        $details[] = "Balance: TZS " . number_format($this->balance_amount, 2);
        if ($this->client) {
            $details[] = "Client: {$this->client->name}";
            if ($this->client->phone) {
                $details[] = "Phone: {$this->client->phone}";
            }
            if ($this->client->email) {
                $details[] = "Email: {$this->client->email}";
            }
        }
        if ($this->project) {
            $details[] = "Project: {$this->project->name}";
        }
        $description = implode("\n", $details);

        // Build URL
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $dates,
            'details' => $description,
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * Get Google Calendar URL attribute
     */
    public function getGoogleCalendarLinkAttribute(): string
    {
        return $this->getGoogleCalendarUrl();
    }

    /**
     * Scope for unpaid invoices with due date
     */
    public function scopeUnpaidWithDueDate($query)
    {
        return $query->where('document_type', 'invoice')
            ->whereNotIn('status', ['paid', 'cancelled', 'void'])
            ->whereNotNull('due_date');
    }

    /**
     * Scope for invoices due today
     */
    public function scopeDueToday($query)
    {
        return $query->unpaidWithDueDate()
            ->whereDate('due_date', now()->toDateString());
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdueInvoices($query)
    {
        return $query->unpaidWithDueDate()
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for upcoming invoices (due in future)
     */
    public function scopeUpcomingDue($query)
    {
        return $query->unpaidWithDueDate()
            ->whereDate('due_date', '>', now()->toDateString());
    }

    /**
     * Check if invoice is due today
     */
    public function isDueToday(): bool
    {
        return $this->due_date && $this->due_date->isToday();
    }

    /**
     * Check if invoice was rescheduled
     */
    public function wasRescheduled(): bool
    {
        return !empty($this->rescheduled_at);
    }

    /**
     * Get the user who rescheduled this invoice
     */
    public function rescheduledByUser()
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }

    /**
     * Get the user who attended to this invoice
     */
    public function attendedByUser()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }
}