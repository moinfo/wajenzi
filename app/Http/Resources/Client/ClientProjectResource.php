<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'project_name' => $this->project_name,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'start_date' => $this->start_date,
            'expected_end_date' => $this->expected_end_date,
            'actual_end_date' => $this->actual_end_date,
            'contract_value' => $this->contract_value,
            'project_type' => $this->whenLoaded('projectType', fn() => $this->projectType?->name),
            'service_type' => $this->whenLoaded('serviceType', fn() => $this->serviceType?->name),
            'project_manager' => $this->whenLoaded('projectManager', fn() => $this->projectManager?->name),
            'invoices_count' => ($this->invoices_count ?? 0) + ($this->billing_invoices_count ?? 0),
            'boqs_count' => $this->whenCounted('boqs'),
            'daily_reports_count' => $this->whenCounted('dailyReports'),
            'phases' => ClientConstructionPhaseResource::collection($this->whenLoaded('constructionPhases')),
        ];
    }
}
