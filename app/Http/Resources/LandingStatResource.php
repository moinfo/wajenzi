<?php

namespace App\Http\Resources;

use App\Models\LandingStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');

        return [
            'id' => $this->id,
            'value' => $this->value,
            'label' => LandingStat::localize($this->label, $lang),
        ];
    }
}
