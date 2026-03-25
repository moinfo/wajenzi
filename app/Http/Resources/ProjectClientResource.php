<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'identification_number' => $this->identification_number,
            'status' => $this->status,
            'file' => $this->file ? asset('storage/' . $this->file) : null,
            'portal_access_enabled' => $this->portal_access_enabled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'client_source' => $this->whenLoaded('client_source', function () {
                return [
                    'id' => $this->client_source->id,
                    'name' => $this->client_source->name,
                ];
            }),
            'created_by' => $this->whenLoaded('user', function () {
                if ($this->user) {
                    return [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ];
                }
                return null;
            }),
            'projects_count' => $this->when(isset($this->projects_count), function () {
                return $this->projects_count;
            }),
            'documents_count' => $this->when(isset($this->documents_count), function () {
                return $this->documents_count;
            }),
            'projects' => $this->whenLoaded('projects', function () {
                return $this->projects->map(fn($p) => [
                    'id' => $p->id,
                    'project_name' => $p->project_name,
                    'status' => $p->status,
                ]);
            }),
        ];
    }
}
