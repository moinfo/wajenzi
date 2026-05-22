<?php

namespace App\Support;

/**
 * Probe an approvable model for the user_id of its creator/submitter.
 *
 * Approvable models in this codebase use inconsistent column names for the creator
 * (created_by_id, create_by_id, created_by, requested_by, requester_id, prepared_by,
 * inspector_id, applicant_id, user_id). This trait centralises the probe order so
 * both the RingleSoft listener path and the legacy ApprovalController path resolve
 * the creator the same way.
 *
 * Probe is ordered most-specific → most-generic so the first hit wins. user_id is
 * last because it's an ambiguous column name across the codebase.
 */
trait ResolvesApprovableCreator
{
    protected array $creatorFieldCandidates = [
        'created_by_id',
        'create_by_id',
        'created_by',
        'creator_id',
        'requested_by',
        'requester_id',
        'prepared_by',
        'inspector_id',
        'applicant_id',
        'user_id',
    ];

    protected function resolveCreatorId($approvable): ?int
    {
        if (!$approvable) {
            return null;
        }
        foreach ($this->creatorFieldCandidates as $field) {
            $value = $approvable->{$field} ?? null;
            if (!empty($value) && is_numeric($value)) {
                return (int) $value;
            }
        }
        return null;
    }
}
