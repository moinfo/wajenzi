<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'record_time' => $this->record_time?->toISOString(),
            'type' => $this->type, // 'in' or 'out'
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ip' => $this->ip,
            'comment' => $this->comment,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
