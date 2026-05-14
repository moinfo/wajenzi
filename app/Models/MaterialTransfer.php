<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class MaterialTransfer extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'material_transfers';

    protected $fillable = [
        'transfer_number',
        'from_project_id',
        'to_project_id',
        'material_request_id',
        'requester_id',
        'approved_by',
        'status',
        'transfer_date',
        'expected_arrival_date',
        'loading_cost',
        'offloading_cost',
        'transportation_cost',
        'total_cost',
        'expenses_sub_category_id',
        'vehicle_info',
        'notes',
        'approved_date',
        'expense_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_arrival_date' => 'date',
        'approved_date' => 'datetime',
        'loading_cost' => 'decimal:2',
        'offloading_cost' => 'decimal:2',
        'transportation_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transfer_number)) {
                $model->transfer_number = self::generateTransferNumber();
            }
            $model->total_cost = (float)$model->loading_cost + (float)$model->offloading_cost + (float)$model->transportation_cost;
        });

        static::updating(function ($model) {
            $model->total_cost = (float)$model->loading_cost + (float)$model->offloading_cost + (float)$model->transportation_cost;
        });

        static::created(static function ($model) {
            if (method_exists($model, 'bypassApprovalProcess') && $model->bypassApprovalProcess()) {
                return;
            }
            $model->approvalStatus()->create([
                'steps' => $model->approvalFlowSteps()->map(fn($item) => $item->toApprovalStatusArray()),
                'status' => ApprovalStatusEnum::SUBMITTED->value,
                'creator_id' => Auth::id(),
            ]);
        });
    }

    public static function generateTransferNumber(): string
    {
        $year = date('Y');
        $prefix = "MT-{$year}-";

        $last = self::where('transfer_number', 'like', "{$prefix}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->transfer_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function getDocumentNumberAttribute(): ?string
    {
        return $this->transfer_number;
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialRequest::class, 'material_request_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function expensesSubCategory(): BelongsTo
    {
        return $this->belongsTo(ExpensesSubCategory::class, 'expenses_sub_category_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialTransferItem::class, 'material_transfer_id');
    }

    public function isApproved(): bool
    {
        return strtoupper($this->status) === 'APPROVED' || strtoupper($this->status) === 'COMPLETED';
    }

    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        return DB::transaction(function () use ($approval) {
            $this->status = 'APPROVED';
            $this->approved_date = now();
            $this->approved_by = auth()->id();
            $this->save();

            // Move stock between projects — BOQ rows and/or free-stock items.
            foreach ($this->items as $item) {
                // BOQ source: mark as used
                if ($item->source_boq_item_id) {
                    $source = ProjectBoqItem::find($item->source_boq_item_id);
                    if ($source) {
                        $source->increment('quantity_used', (float) $item->quantity);
                    }
                }

                // BOQ destination: mark as received
                if ($item->destination_boq_item_id) {
                    $dest = ProjectBoqItem::find($item->destination_boq_item_id);
                    if ($dest) {
                        $dest->increment('quantity_received', (float) $item->quantity);
                    }
                }

                // Free-stock source: deduct from on-hand quantity
                if ($item->source_stock_item_id) {
                    $stockSource = ProjectStockItem::find($item->source_stock_item_id);
                    if ($stockSource) {
                        $stockSource->decrement('quantity_on_hand', (float) $item->quantity);
                    }
                }

                // Free-stock destination: add to existing stock item or create new one
                if ($item->source_stock_item_id || ($item->destination_stock_item_id === null && !$item->source_boq_item_id && !$item->destination_boq_item_id)) {
                    $stockSource = $item->source_stock_item_id ? ProjectStockItem::find($item->source_stock_item_id) : null;

                    if ($item->destination_stock_item_id) {
                        ProjectStockItem::where('id', $item->destination_stock_item_id)
                            ->increment('quantity_on_hand', (float) $item->quantity);
                    } elseif ($stockSource) {
                        // Auto-create or find matching stock item at destination
                        $destStock = ProjectStockItem::firstOrCreate(
                            ['project_id' => $this->to_project_id, 'description' => $item->description, 'unit' => $item->unit],
                            ['created_by_id' => $this->approved_by]
                        );
                        $destStock->increment('quantity_on_hand', (float) $item->quantity);
                        $item->destination_stock_item_id = $destStock->id;
                        $item->saveQuietly();
                    }
                }
            }

            // Record loading + offloading + transport cost as a pre-approved
            // expense against the destination project so it lands in reports.
            if ($this->total_cost > 0 && $this->expenses_sub_category_id && !$this->expense_id) {
                $expense = Expense::create([
                    'project_id' => $this->to_project_id,
                    'expenses_sub_category_id' => $this->expenses_sub_category_id,
                    'amount' => $this->total_cost,
                    'description' => "Material transfer {$this->transfer_number} from "
                        . ($this->fromProject->name ?? $this->fromProject->project_name ?? 'project '.$this->from_project_id)
                        . sprintf(' (loading %s, offloading %s, transport %s)',
                            number_format($this->loading_cost, 2),
                            number_format($this->offloading_cost, 2),
                            number_format($this->transportation_cost, 2)),
                    'date' => $this->transfer_date,
                    'status' => 'APPROVED',
                    'document_number' => $this->transfer_number,
                ]);
                $this->expense_id = $expense->id;
                $this->saveQuietly();
            }

            return true;
        });
    }
}
