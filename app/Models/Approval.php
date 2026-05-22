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


    static function getApprovalStages($document_id, $document_type_id)
    {
        return DB::select(
            "SELECT al.*, al.id AS order_id, al.`order` AS `order`,
                    al.`approval_document_types_id` AS document_id,
                    al.`user_group_id` AS `user_group_id`,
                    al.`role_id` AS `role_id`,
                    ug.name AS user_group_name,
                    r.name AS role_name,
                    a.status AS status,
                    a.comments AS comments
             FROM approval_levels al
             LEFT JOIN approvals a    ON (a.approval_level_id = al.id AND a.document_id = ?)
             LEFT JOIN user_groups ug ON (ug.id = al.user_group_id)
             LEFT JOIN roles r        ON (r.id = al.role_id)
             WHERE al.`order` > 0 AND al.approval_document_types_id = ?
             ORDER BY al.`order` ASC",
            [$document_id, $document_type_id]
        );
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

    /**
     * Resolve the users authorised to act on a given approval stage.
     *
     * Preference order:
     *   1. Spatie role (al.role_id) — new system, role-based.
     *   2. Legacy user_group (al.user_group_id + assign_user_groups) — fallback for
     *      approval_levels rows not yet migrated to a Spatie role.
     *
     * @param  object  $stage  A stage row from getApprovalStages().
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public static function getApproversFor($stage)
    {
        if (!empty($stage->role_id)) {
            return User::whereHas('roles', function ($q) use ($stage) {
                $q->where('id', $stage->role_id);
            })->get();
        }
        if (!empty($stage->user_group_id)) {
            return AssignUserGroup::getUsersInGroup($stage->user_group_id);
        }
        return User::query()->whereKey([])->get();
    }
}

