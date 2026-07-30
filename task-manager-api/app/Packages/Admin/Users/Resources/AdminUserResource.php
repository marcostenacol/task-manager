<?php

namespace App\Packages\Admin\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_path' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'role' => [
                'id' => $this->role_id ?? ($this->role->id ?? null),
                'name' => $this->role_name ?? ($this->role->name ?? null),
                'slug' => $this->role_slug ?? ($this->role->slug ?? null),
                'level' => $this->role_level ?? ($this->role->level ?? null),
                'color' => $this->role_color ?? ($this->role->color ?? null),
            ],
            'status' => [
                'name' => $this->status_name ?? ($this->lastStatus->name ?? null),
                'slug' => $this->status_slug ?? ($this->lastStatus->slug ?? null),
            ],
            'organization' => ($this->organization_id ?? $this->active_organization_id)
                ? [
                    'id' => $this->organization_id ?? $this->activeOrganization?->id,
                    'name' => $this->organization_name ?? $this->activeOrganization?->name,
                    'slug' => $this->organization_slug ?? $this->activeOrganization?->slug,
                ]
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
