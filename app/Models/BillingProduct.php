<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingProduct extends Model
{
    use HasFactory;
    
    protected $table = 'billing_products_services';
    
    protected $fillable = [
        'type',
        'code',
        'name',
        'description',
        'category',
        'unit_of_measure',
        'unit_price',
        'purchase_price',
        'tax_rate_id',
        'sku',
        'barcode',
        'track_inventory',
        'current_stock',
        'minimum_stock',
        'reorder_level',
        'is_active'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'current_stock' => 'decimal:4',
        'minimum_stock' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function taxRate()
    {
        return $this->belongsTo(BillingTaxRate::class, 'tax_rate_id');
    }

    public function documentItems()
    {
        return $this->hasMany(BillingDocumentItem::class, 'product_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    public function generateCode($type = null)
    {
        $type = $type ?: $this->type;
        $prefix = strtoupper(substr($type, 0, 3)); // PRO for product, SER for service
        
        $lastProduct = self::where('code', 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastProduct && $lastProduct->code) {
            $lastNumber = intval(substr($lastProduct->code, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($product) {
            if (!$product->code) {
                $product->code = $product->generateCode();
            }
        });
    }
}