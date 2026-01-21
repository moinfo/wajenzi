<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'identification_number' => $this->identification_number,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
