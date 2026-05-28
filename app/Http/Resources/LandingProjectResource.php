<?php

namespace App\Http\Resources;

use App\Models\LandingProject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-facing portfolio project shape for the mobile landing screen.
 *
 * Multilingual fields are resolved to a single string using the `lang` query
 * param (default `en`). The optional `device_id` query param is used to flag
 * whether the current visitor has already liked the project.
 */
class LandingProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');
        $deviceId = $request->query('device_id');

        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        return [
            'id' => $this->id,
            'title' => LandingProject::localize($this->title, $lang),
            'category' => LandingProject::localize($this->category, $lang),
            'description' => LandingProject::localize($this->description, $lang),
            'price_tzs' => $this->price_tzs !== null ? (float) $this->price_tzs : null,
            'price_usd' => $this->price_usd !== null ? (float) $this->price_usd : null,
            'youtube_url' => $this->youtube_url,
            'model_3d_url' => $this->model_3d_url,
            'has_video' => filled($this->youtube_url),
            'has_3d' => filled($this->model_3d_url),
            'likes_count' => (int) $this->likes_count,
            'is_featured' => (bool) $this->is_featured,
            'liked' => $deviceId
                ? $this->likes->contains(fn ($like) => $like->device_id === $deviceId)
                : false,
            'image' => $primary?->file,
            'images' => $this->images->pluck('file')->values(),
            'amenities' => $this->amenities
                ->map(fn ($a) => LandingProject::localize($a->label, $lang))
                ->filter()
                ->values(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
