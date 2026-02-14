<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'design_type' => $this->design_type,
            'version' => $this->version,
            'file_url' => $this->file_path ? asset('storage/' . $this->file_path) : null,
            'status' => $this->status,
            'client_feedback' => $this->client_feedback,
        ];
    }
}
