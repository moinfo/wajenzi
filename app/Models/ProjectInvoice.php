<?php
// ProjectInvoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectInvoice extends Model
{
    use HasFactory;

    protected $table = 'project_invoices';

    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'status',
        'due_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class, 'invoice_id');
    }
}
