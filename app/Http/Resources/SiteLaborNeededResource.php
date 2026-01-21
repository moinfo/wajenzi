<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteLaborNeededResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_daily_report_id' => $this->site_daily_report_id,
            'labor_type' => $this->labor_type,
            'quantity' => $this->quantity,
            'rate_per_day' => $this->rate_per_day,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
        ];
    }
}
