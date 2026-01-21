<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SitePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_daily_report_id' => $this->site_daily_report_id,
            'recipient_name' => $this->recipient_name,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
        ];
    }
}
