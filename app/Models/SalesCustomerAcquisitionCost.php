<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCustomerAcquisitionCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_daily_report_id',
        'marketing_cost',
        'sales_cost',
        'other_cost',
        'total_cost',
        'new_customers',
        'cac_value',
        'notes'
    ];

    protected $casts = [
        'marketing_cost' => 'decimal:2',
        'sales_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cac_value' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function salesDailyReport()
    {
        return $this->belongsTo(SalesDailyReport::class);
    }

    /**
     * Calculate and update CAC value
     */
    public function calculateCAC()
    {
        $this->total_cost = $this->marketing_cost + $this->sales_cost + $this->other_cost;
        
        if ($this->new_customers > 0) {
            $this->cac_value = $this->total_cost / $this->new_customers;
        } else {
            $this->cac_value = 0;
        }
        
        return $this;
    }

    /**
     * Boot method to calculate CAC before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->calculateCAC();
        });
    }
}