<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientSiteVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'visit_date' => $this->visit_date,
            'status' => $this->status,
            'location' => $this->location,
            'description' => $this->description,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'inspector' => $this->whenLoaded('inspector', fn() => $this->inspector?->name),
        ];
    }
}
