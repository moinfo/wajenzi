<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ImprestRequest extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'imprest_requests';

    public $fillable = ['document_number','description','amount','status','create_by_id','expenses_sub_category_id','file','date','project_id','retirement_file','retirement_notes','retired_at'];

    protected $casts = [
        'retired_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return strtoupper($this->status ?? '') === 'APPROVED';
    }

    public function isRetired(): bool
    {
        return !empty($this->retirement_file);
    }

    public function enableAutoSubmit(): bool
    {
        return true;
    }

    protected static function booted(): void
    {
        static::creating(static function (ImprestRequest $model) {
            if (empty($model->status) || strtoupper($model->status) === 'CREATED') {
                $model->status = 'SUBMITTED';
            }
            if ($model->getKey() !== null) {
                self::purgeApprovalArtifacts($model->getKey());
            }
        });

        static::created(static function (ImprestRequest $model) {
            self::purgeApprovalArtifacts($model->getKey(), excludeStatusIds: [
                optional($model->approvalStatus()->latest('id')->first())->id,
            ]);
        });

        static::deleting(static function (ImprestRequest $model) {
            self::purgeApprovalArtifacts($model->getKey());
        });
    }

    private static function purgeApprovalArtifacts(int|string|null $imprestId, array $excludeStatusIds = []): void
    {
        if ($imprestId === null) {
            return;
        }

        $type = self::class;

        \Illuminate\Support\Facades\DB::table('process_approvals')
            ->where('approvable_type', $type)
            ->where('approvable_id', $imprestId)
            ->delete();

        $statusQuery = \Illuminate\Support\Facades\DB::table('process_approval_statuses')
            ->where('approvable_type', $type)
            ->where('approvable_id', $imprestId);

        $excludeStatusIds = array_filter($excludeStatusIds);
        if (!empty($excludeStatusIds)) {
            $statusQuery->whereNotIn('id', $excludeStatusIds);
        }

        $statusQuery->delete();
    }

    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
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
