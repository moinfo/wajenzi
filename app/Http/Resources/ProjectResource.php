<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $delay = null;
        if ($this->expected_end_date && $this->actual_end_date) {
            $delay = $this->expected_end_date->diffInDays($this->actual_end_date, false);
        }

        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'project_name' => $this->project_name,
            'description' => $this->description,
            'status' => $this->approvalStatus?->status ?? $this->status,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->toDateString(),
            'expected_end_date' => $this->expected_end_date?->toDateString(),
            'actual_end_date' => $this->actual_end_date?->toDateString(),
            'contract_value' => $this->contract_value,
            'planned_duration' => $this->planned_duration,
            'actual_duration' => $this->actual_duration,
            'delay_days' => $delay,
            'location' => $this->location,
            'client' => $this->whenLoaded('client', function () {
                if ($this->client) {
                    return [
                        'id' => $this->client->id,
                        'name' => $this->client->first_name . ' ' . $this->client->last_name,
                        'email' => $this->client->email,
                        'phone' => $this->client->phone_number,
                    ];
                }
                return null;
            }),
            'project_type' => $this->whenLoaded('projectType', function () {
                if ($this->projectType) {
                    return [
                        'id' => $this->projectType->id,
                        'name' => $this->projectType->name,
                    ];
                }
                return null;
            }),
            'service_type' => $this->whenLoaded('serviceType', function () {
                if ($this->serviceType) {
                    return [
                        'id' => $this->serviceType->id,
                        'name' => $this->serviceType->name,
                    ];
                }
                return null;
            }),
            'salesperson' => $this->whenLoaded('salesperson', function () {
                if ($this->salesperson) {
                    return [
                        'id' => $this->salesperson->id,
                        'name' => $this->salesperson->name,
                        'email' => $this->salesperson->email,
                    ];
                }
                return null;
            }),
            'project_manager' => $this->whenLoaded('projectManager', function () {
                if ($this->projectManager) {
                    return [
                        'id' => $this->projectManager->id,
                        'name' => $this->projectManager->name,
                        'email' => $this->projectManager->email,
                    ];
                }
                return null;
            }),
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
