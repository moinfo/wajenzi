<?php

namespace App\Http\Resources;

use App\Models\LandingAward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingAwardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');

        return [
            'id' => $this->id,
            'year' => $this->year,
            'title' => LandingAward::localize($this->title, $lang),
            'subtitle' => LandingAward::localize($this->subtitle, $lang),
            'organization' => LandingAward::localize($this->organization, $lang),
            'description' => LandingAward::localize($this->description, $lang),
            'image' => $this->image,
        ];
    }
}
