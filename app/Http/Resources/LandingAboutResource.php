<?php

namespace App\Http\Resources;

use App\Models\LandingAbout;
use App\Models\LandingTeamMember;
use App\Models\LandingValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Singleton "About" payload for the mobile landing screen. The resource is
 * given an array shaped as ['about' => LandingAbout, 'values' => Collection,
 * 'team' => Collection] so it can resolve all multilingual fields against the
 * requested ?lang= and assemble the final contract in one place.
 */
class LandingAboutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');

        /** @var LandingAbout|null $about */
        $about = $this->resource['about'] ?? null;
        $values = $this->resource['values'] ?? collect();
        $team = $this->resource['team'] ?? collect();

        return [
            'founded_year' => $about?->founded_year,
            'tagline' => LandingAbout::localize($about?->tagline, $lang),
            'story' => LandingAbout::localize($about?->story, $lang),
            'mission' => LandingAbout::localize($about?->mission, $lang),
            'vision' => LandingAbout::localize($about?->vision, $lang),
            'address' => $about?->address,
            'phone' => $about?->phone,
            'email' => $about?->email,
            'working_hours' => LandingAbout::localize($about?->working_hours, $lang),
            'values' => collect($values)->map(fn (LandingValue $v) => [
                'id' => $v->id,
                'title' => LandingValue::localize($v->title, $lang),
                'description' => LandingValue::localize($v->description, $lang),
            ])->values(),
            'team' => collect($team)->map(fn (LandingTeamMember $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'role' => LandingTeamMember::localize($m->role, $lang),
                'bio' => LandingTeamMember::localize($m->bio, $lang),
                'image' => $m->image,
            ])->values(),
        ];
    }
}
