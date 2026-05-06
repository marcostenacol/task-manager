<?php

namespace App\Packages\Admin\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_path' => $this->avatar_path,
            'role' => [
                'name' => $this->role_name ?? ($this->role->name ?? null),
                'slug' => $this->role_slug ?? ($this->role->slug ?? null),
            ],
            'status' => [
                'name' => $this->status_name ?? ($this->lastStatus->name ?? null),
                'slug' => $this->status_slug ?? ($this->lastStatus->slug ?? null),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
