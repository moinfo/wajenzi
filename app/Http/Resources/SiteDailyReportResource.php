<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteDailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_date' => $this->report_date?->toDateString(),
            'site_id' => $this->site_id,
            'supervisor_id' => $this->supervisor_id,
            'prepared_by' => $this->prepared_by,
            'progress_percentage' => $this->progress_percentage,
            'next_steps' => $this->next_steps,
            'challenges' => $this->challenges,
            'status' => $this->status,
            'site' => $this->whenLoaded('site', function () {
                return new SiteResource($this->site);
            }),
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->name,
                ];
            }),
            'prepared_by_user' => $this->whenLoaded('preparedBy', function () {
                return [
                    'id' => $this->preparedBy->id,
                    'name' => $this->preparedBy->name,
                ];
            }),
            'work_activities' => SiteWorkActivityResource::collection($this->whenLoaded('workActivities')),
            'materials_used' => SiteMaterialUsedResource::collection($this->whenLoaded('materialsUsed')),
            'payments' => SitePaymentResource::collection($this->whenLoaded('payments')),
            'labor_needed' => SiteLaborNeededResource::collection($this->whenLoaded('laborNeeded')),
            'total_payments' => $this->when(isset($this->total_payments), $this->total_payments),
            'can_edit' => $this->canEdit(),
            'can_submit' => $this->canSubmit(),
            'can_approve' => $this->canApprove(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
