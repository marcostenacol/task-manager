<?php

namespace App\Packages\Admin\Roles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'level' => $this->level,
            'color' => $this->color,
            'organization_id' => $this->organization_id,
            'permission_ids' => $this->permissions->pluck('id'),
        ];
    }
}
