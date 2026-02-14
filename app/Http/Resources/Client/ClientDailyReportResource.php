<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_date' => $this->report_date?->toDateString(),
            'weather_conditions' => $this->weather_conditions,
            'work_completed' => $this->work_completed,
            'materials_used' => $this->materials_used,
            'labor_hours' => $this->labor_hours,
            'issues_faced' => $this->issues_faced,
            'supervisor' => $this->whenLoaded('supervisor', fn() => $this->supervisor?->name),
        ];
    }
}
