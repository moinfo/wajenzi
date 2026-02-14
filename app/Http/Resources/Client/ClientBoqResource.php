<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientBoqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'type' => $this->type,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'sections' => ClientBoqSectionResource::collection($this->whenLoaded('sections', fn() =>
                $this->sections->whereNull('parent_id')
            )),
            'items' => ClientBoqItemResource::collection($this->whenLoaded('items', fn() =>
                $this->items->whereNull('section_id')
            )),
        ];
    }
}
