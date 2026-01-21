<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'employee_type' => $this->employee_type,
            'designation' => $this->designation,
            'gender' => $this->gender,
            'address' => $this->address,
            'status' => $this->status,
            'profile_url' => $this->profile ? Storage::disk('public')->url($this->profile) : null,
            'signature_url' => $this->file ? Storage::disk('public')->url($this->file) : null,
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                ];
            }),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
