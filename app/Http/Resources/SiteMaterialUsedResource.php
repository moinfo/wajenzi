<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteMaterialUsedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_daily_report_id' => $this->site_daily_report_id,
            'material_name' => $this->material_name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
        ];
    }
}
