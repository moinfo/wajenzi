<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    use HasFactory;
    protected $fillable = [
        'approval_document_types_id', 'user_group_id', 'description',
        'action','order'
    ];


    public static function getUsersApprovals($approval_document_types_id)
    {
        return ApprovalLevel::where('approval_document_types_id',$approval_document_types_id)->whereNotIn('order',[0])->get() ?? [];
    }

    public static function getUserGroupName($approval_id)
    {
        return ApprovalLevel::select('user_groups.keyword as user_group_name')->join('user_groups','user_groups.id','=','approval_levels.user_group_id')->where('approval_levels.id',$approval_id)->get()->first()->user_group_name;
    }

    public function approvalDocumentType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApprovalDocumentType::class, "approval_document_types_id");
    }
    public function userGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(userGroup::class);
    }
    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
