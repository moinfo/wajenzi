<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientBoqSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'items' => ClientBoqItemResource::collection($this->whenLoaded('items')),
            'children' => ClientBoqSectionResource::collection($this->whenLoaded('children')),
        ];
    }
}
