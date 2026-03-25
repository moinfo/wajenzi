<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        try {
            $receiptUrl = null;
            if ($this->receipt) {
                try {
                    $receiptUrl = Storage::disk('public')->url($this->receipt);
                } catch (\Throwable $e) {
                    $receiptUrl = null;
                }
            }

            $data = [
                'id' => $this->id,
                'project_id' => $this->project_id,
                'cost_category_id' => $this->cost_category_id,
                'description' => $this->description,
                'amount' => $this->amount,
                'expense_date' => $this->expense_date ? ($this->expense_date instanceof \Carbon\Carbon ? $this->expense_date->toDateString() : $this->expense_date) : null,
                'receipt_path' => $receiptUrl,
                'status' => $this->status ?? 'draft',
                'remarks' => $this->remarks ?? null,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];

            if ($this->relationLoaded('project')) {
                $data['project'] = [
                    'id' => $this->project->id,
                    'project_name' => $this->project->project_name ?? $this->project->name ?? null,
                    'document_number' => $this->project->document_number ?? null,
                ];
            }

            if ($this->relationLoaded('costCategory') && $this->costCategory) {
                $data['cost_category'] = [
                    'id' => $this->costCategory->id,
                    'name' => $this->costCategory->name,
                ];
            }

            if ($this->relationLoaded('creator') && $this->creator) {
                $data['creator'] = [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [
                'id' => $this->id ?? null,
                'description' => $this->description ?? 'Error loading expense',
                'error' => 'Failed to format expense',
            ];
        }
    }
}
