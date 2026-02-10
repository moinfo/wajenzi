<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'item_type',
        'product_service_id',
        'item_code',
        'item_name',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate_id',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'sort_order'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2'
    ];

    public function document()
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }

    public function productService()
    {
        return $this->belongsTo(BillingProduct::class, 'product_service_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(BillingTaxRate::class, 'tax_rate_id');
    }

    public function calculateLineTotal()
    {
        $subtotal = $this->quantity * $this->unit_price;
        
        // Calculate discount
        $discountAmount = 0;
        if ($this->discount_type === 'percentage' && $this->discount_value > 0) {
            $discountAmount = ($subtotal * $this->discount_value) / 100;
        } elseif ($this->discount_type === 'fixed' && $this->discount_value > 0) {
            $discountAmount = $this->discount_value;
        }
        
        // Calculate tax
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = 0;
        if ($this->tax_percentage > 0) {
            $taxAmount = ($taxableAmount * $this->tax_percentage) / 100;
        }
        
        // Line total is the pre-tax amount (tax is added at document level)
        $lineTotal = $taxableAmount;

        // Update the item
        $this->update([
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal
        ]);
        
        return $this;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            if (!$item->sort_order) {
                $maxOrder = static::where('document_id', $item->document_id)->max('sort_order');
                $item->sort_order = $maxOrder ? $maxOrder + 1 : 1;
            }
        });
        
        static::saving(function ($item) {
            // Auto-calculate line total before saving
            $subtotal = $item->quantity * $item->unit_price;
            
            // Calculate discount
            $discountAmount = 0;
            if ($item->discount_type === 'percentage' && $item->discount_value > 0) {
                $discountAmount = ($subtotal * $item->discount_value) / 100;
            } elseif ($item->discount_type === 'fixed' && $item->discount_value > 0) {
                $discountAmount = $item->discount_value;
            }
            
            // Calculate tax
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = 0;
            if ($item->tax_percentage > 0) {
                $taxAmount = ($taxableAmount * $item->tax_percentage) / 100;
            }
            
            // Set calculated values (line_total is pre-tax, tax added at document level)
            $item->discount_amount = $discountAmount;
            $item->tax_amount = $taxAmount;
            $item->line_total = $taxableAmount;
        });
        
        static::saved(function ($item) {
            // Recalculate document totals after item is saved
            if ($item->document) {
                $item->document->calculateTotals();
            }
        });
        
        static::deleted(function ($item) {
            // Recalculate document totals after item is deleted
            if ($item->document) {
                $item->document->calculateTotals();
            }
        });
    }
}