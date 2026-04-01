<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'document_id' => $this->document_id,
            'client_id' => $this->client_id,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'bank_name' => $this->bank_name,
            'cheque_number' => $this->cheque_number,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_receipt_signed' => $this->is_receipt_signed,
            'document' => $this->whenLoaded('document', function () {
                return [
                    'id' => $this->document->id,
                    'document_number' => $this->document->document_number,
                    'document_type' => $this->document->document_type,
                    'client' => $this->document->client ? [
                        'id' => $this->document->client->id,
                        'first_name' => $this->document->client->first_name,
                        'last_name' => $this->document->client->last_name,
                        'full_name' => $this->document->client->full_name,
                        'email' => $this->document->client->email,
                        'phone_number' => $this->document->client->phone_number,
                    ] : null,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'contact_person' => $this->client->contact_person,
                    'company_name' => $this->client->company_name,
                    'email' => $this->client->email,
                    'phone' => $this->client->phone,
                ];
            }),
            'received_by' => $this->whenLoaded('receiver', function () {
                return [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
