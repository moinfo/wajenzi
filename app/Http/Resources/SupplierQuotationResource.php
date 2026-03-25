<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierQuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_number' => $this->quotation_number,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier'),
            'material_request_id' => $this->material_request_id,
            'material_request' => $this->whenLoaded('materialRequest', function () {
                return [
                    'id' => $this->materialRequest->id,
                    'request_number' => $this->materialRequest->request_number,
                    'project_name' => $this->materialRequest->project?->project_name ?? null,
                ];
            }),
            'items' => $this->whenLoaded('items'),
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'validity_days' => $this->validity_days,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
