<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'project_name' => $this->project_name,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'expected_end_date' => $this->expected_end_date?->toDateString(),
            'actual_end_date' => $this->actual_end_date?->toDateString(),
            'client' => $this->whenLoaded('client', function () {
                return new ProjectClientResource($this->client);
            }),
            'project_type' => $this->whenLoaded('projectType', function () {
                return [
                    'id' => $this->projectType->id,
                    'name' => $this->projectType->name,
                ];
            }),
            'sites' => SiteResource::collection($this->whenLoaded('sites')),
            'team_role' => $this->whenPivotLoaded('project_team_members', function () {
                return [
                    'role' => $this->pivot->role,
                    'assigned_date' => $this->pivot->assigned_date,
                    'status' => $this->pivot->status,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
