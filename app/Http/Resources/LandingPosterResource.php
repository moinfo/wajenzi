<?php

namespace App\Http\Resources;

use App\Models\LandingPoster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingPosterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');

        return [
            'id' => $this->id,
            'title' => LandingPoster::localize($this->title, $lang),
            'subtitle' => LandingPoster::localize($this->subtitle, $lang),
            'image' => $this->image,
            'link_url' => $this->link_url,
            'youtube_url' => $this->youtube_url,
            'has_link' => filled($this->link_url),
            'has_video' => filled($this->youtube_url),
        ];
    }
}
