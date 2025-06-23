<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_document_types_id', 'statutory_payment_id', 'user_id', 'user_group_id', 'approval_level_id',
        'comments', 'status', 'approval_date','document_id'

    ];

    public static function getApproved($approval_level_id,$document_id)
    {
        return Approval::Where('approval_level_id',$approval_level_id)->Where('document_id',$document_id)->get() ?? [];
    }

    public static function getApprovedDocument($approval_level_id,$approval_document_type_id,$document_id)
    {
        return Approval::Where('approval_document_types_id',$approval_document_type_id)->Where('document_id',$document_id)->Where('approval_level_id',$approval_level_id)->get()->first() ?? 0;
    }

    public function approvalDocumentTypes(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApprovalDocumentType::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    public function approvalLevel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApprovalLevel::class);
    }

    public function statutoryPayment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StatutoryPayment::class);
    }


    static function getApprovalStages($document_id,$document_type_id)
    {
        $query = DB::SELECT("SELECT *, al.id AS order_id, al.`order` as `order`, al.`approval_document_types_id` AS document_id,
       al.`user_group_id` AS `user_group_id`, ug.name AS user_group_name FROM approval_levels al
        LEFT JOIN approvals a ON (a.approval_level_id=al.id AND a.`document_id` = '$document_id')
        LEFT JOIN user_groups ug ON (ug.id = al.`user_group_id`) WHERE al.order > 0 AND al.approval_document_types_id  = '$document_type_id'");
        return $query;
    }

    static function getNextApproval($document_id,$document_type_id)
    {
        $stages = self::getApprovalStages($document_id,$document_type_id);
        foreach ($stages as $stage) {
            if (($stage->status != 'APPROVED')) {
                return $stage;
            }
        }
        return false;
    }

    static function isApprovalCompleted($document_id,$document_type_id)
    {
        return self::getNextApproval($document_id,$document_type_id) === false;
    }

    static function isRejected($document_id,$document_type_id)
    {
        $stages = self::getApprovalStages($document_id,$document_type_id);
        foreach ($stages as $stage) {
            if ($stage->status == 'REJECTED') {
                return $stage;
            }
        }
        return false;
    }
}

