<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'reference_number' => $this->reference_number,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'valid_until_date' => $this->valid_until_date?->toDateString(),
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,
            'late_fee_amount' => $this->late_fee_amount,
            'status' => $this->status,
            'payment_terms' => $this->payment_terms,
            'currency_code' => $this->currency_code,
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'client' => $this->whenLoaded('client', function () {
                return new ProjectClientResource($this->client);
            }),
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'project_name' => $this->project->project_name,
                ];
            }),
            'items' => BillingDocumentItemResource::collection($this->whenLoaded('items')),
            'payments' => BillingPaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
