<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReportActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_daily_report_id',
        'invoice_no',
        'invoice_sum',
        'activity',
        'status'
    ];

    protected $casts = [
        'invoice_sum' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function salesDailyReport()
    {
        return $this->belongsTo(SalesDailyReport::class);
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'not_paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }
}