<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteWorkActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_daily_report_id' => $this->site_daily_report_id,
            'activity_name' => $this->activity_name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'workers_count' => $this->workers_count,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
