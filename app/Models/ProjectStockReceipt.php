<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStockReceipt extends Model
{
    use HasFactory;

    protected $table = 'project_stock_receipts';

    protected $fillable = [
        'receipt_number',
        'project_id',
        'supplier',
        'receipt_date',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PSR-{$year}-";
        $last = self::where('receipt_number', 'like', "{$prefix}%")
            ->orderBy('receipt_number', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->receipt_number, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectStockReceiptItem::class, 'receipt_id');
    }

    public function totalQty(): float
    {
        return (float) $this->items->sum('quantity');
    }
}
