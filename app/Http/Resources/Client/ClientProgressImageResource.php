<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProgressImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->file ? asset('storage/' . $this->file) : null,
            'taken_at' => $this->taken_at?->toDateString(),
            'phase' => $this->whenLoaded('constructionPhase', fn() =>
                $this->constructionPhase?->phase_name
            ),
        ];
    }
}
