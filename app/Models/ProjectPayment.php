<?php
// Financial Management Models
// ProjectPayment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPayment extends Model
{
    protected $table = 'project_payments';

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProjectInvoice::class, 'invoice_id');
    }
}
