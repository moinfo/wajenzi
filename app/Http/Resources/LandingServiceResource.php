<?php

namespace App\Http\Resources;

use App\Models\LandingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');

        return [
            'id' => $this->id,
            'title' => LandingService::localize($this->title, $lang),
            'short_description' => LandingService::localize($this->short_description, $lang),
            'full_description' => LandingService::localize($this->full_description, $lang),
            'image' => $this->image,
            'features' => collect($this->features ?? [])
                ->map(fn ($f) => is_array($f) ? LandingService::localize($f, $lang) : $f)
                ->filter()
                ->values(),
        ];
    }
}
