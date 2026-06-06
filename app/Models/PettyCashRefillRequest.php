<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use App\Models\Concerns\CascadesApprovalRecords;

class PettyCashRefillRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

    protected $table = 'petty_cash_refill_requests';

    protected $fillable = ['document_number','balance','refill_amount','status','create_by_id','file','date', 'charts_account_id'];

    protected static function boot(): void
    {
        parent::boot();

        static::created(static function (PettyCashRefillRequest $model) {
            if (!$model->bypassApprovalProcess()) {
                $model->approvalStatus()->create([
                    'steps'      => $model->approvalFlowSteps()->map(fn($s) => $s->toApprovalStatusArray()),
                    'status'     => 'Created',
                    'creator_id' => Auth::id(),
                ]);
            }
        });
    }

    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'approved';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'charts_account_id');
    }

    public function getTotalRefillAmountFromBeginning(){
        return $this->where('status', 'approved')->sum('refill_amount');
    }

    public static function getCurrentBalanceBetweenPettyCashRefillRequestAndImprestRequest(){
        return self::where('status', 'approved')->sum('refill_amount') - \App\Models\ImprestRequest::where('status', 'approved')->sum('amount');
    }
}
