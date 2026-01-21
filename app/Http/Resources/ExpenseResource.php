<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'expense_category_id' => $this->expense_category_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
            'receipt_path' => $this->receipt ? Storage::disk('public')->url($this->receipt) : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'project_name' => $this->project->project_name,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
