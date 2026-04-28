<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ImprestRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'imprest_requests';

    public $fillable = ['document_number','description','amount','status','create_by_id','expenses_sub_category_id','file','date','project_id'];

    protected static function boot(): void
    {
        parent::boot();

        static::created(static function (ImprestRequest $model) {
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

    public function expenseSubCategory(){
        return $this->belongsTo(ExpensesSubCategory::class);
    }

    public function ImprestFromBeginning(){
        return $this->where('status', 'approved')->sum('amount');
    }

    public function project(){
        return $this->belongsTo(Project::class);
    }
}
