<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStockItem extends Model
{
    use HasFactory;

    protected $table = 'project_stock_items';

    protected $fillable = [
        'project_id',
        'item_code',
        'description',
        'unit',
        'quantity_on_hand',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->item_code)) {
                $model->item_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $prefix = "FSI-{$year}-";
        $last = self::where('item_code', 'like', "{$prefix}%")
            ->orderBy('item_code', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->item_code, -4)) + 1 : 1;
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
}
