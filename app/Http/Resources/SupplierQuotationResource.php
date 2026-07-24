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
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier' => $this->whenLoaded('supplier'),
            'material_request_id' => $this->material_request_id,
            'material_request' => $this->whenLoaded('materialRequest', function () {
                return [
                    'id' => $this->materialRequest->id,
                    'request_number' => $this->materialRequest->request_number,
                    'project_name' => $this->materialRequest->project?->project_name
                        ?? $this->materialRequest->project?->name,
                ];
            }),
            'quotation_date' => $this->quotation_date?->format('Y-m-d'),
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'delivery_time_days' => $this->delivery_time_days,
            'payment_terms' => $this->payment_terms,
            'unit_price' => (float) $this->unit_price,
            'quantity' => (float) $this->quantity,
            'total_amount' => (float) $this->total_amount,
            'vat_amount' => (float) $this->vat_amount,
            'grand_total' => (float) $this->grand_total,
            'effective_unit_price' => (float) $this->effective_unit_price,
            'file' => $this->file,
            'status' => $this->status,
            'is_expired' => $this->isExpired(),
            'is_selected' => $this->isSelected(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'material_request_item_id' => $item->material_request_item_id,
                    'boq_item_id' => $item->boq_item_id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'sort_order' => (int) $item->sort_order,
                ])->values();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
