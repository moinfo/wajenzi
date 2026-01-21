<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingDocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'sort_order' => $this->sort_order,
        ];
    }
}
