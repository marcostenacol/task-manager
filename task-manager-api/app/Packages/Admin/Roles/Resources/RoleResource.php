<?php

namespace App\Packages\Admin\Roles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'level' => $this->level,
            'color' => $this->color,
            'scope' => $this->scope,
            'permissions_count' => $this->permissions_count ?? $this->permissions->count(),
        ];
    }
}
