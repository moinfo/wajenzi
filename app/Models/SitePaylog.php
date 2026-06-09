<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitePaylog extends Model
{
    use HasFactory;

    public const CATEGORY_MATERIAL = 'material';
    public const CATEGORY_LABOUR   = 'labour';

    public static function categories(): array
    {
        return [
            self::CATEGORY_MATERIAL => 'Material',
            self::CATEGORY_LABOUR   => 'Labour',
        ];
    }

    protected $fillable = [
        'site_id',
        'site_payment_request_id',
        'project_id',
        'payment_date',
        'category',
        'payee_name',
        'reason',
        'payment_channel_id',
        'account_name',
        'amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function request()
    {
        return $this->belongsTo(SitePaymentRequest::class, 'site_payment_request_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('payment_date', $year)
                     ->whereMonth('payment_date', $month);
    }
}
