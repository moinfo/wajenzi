<?php
// ProjectExpense.php (Project Cost)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    use HasFactory;

    protected $table = 'project_expenses';

    protected $fillable = [
        'project_id',
        'cost_category_id',
        'supplier_receiving_id',
        'expense_subtype',
        'amount',
        'description',
        'remarks',
        'receipt',
        'expense_date',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCategory(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierReceiving(): BelongsTo
    {
        return $this->belongsTo(SupplierReceiving::class, 'supplier_receiving_id');
    }

    /**
     * Get formatted cost amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'TZS ' . number_format($this->amount, 2);
    }
}
