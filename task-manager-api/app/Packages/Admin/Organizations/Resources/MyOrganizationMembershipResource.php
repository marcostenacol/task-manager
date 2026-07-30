<?php

namespace App\Packages\Admin\Organizations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyOrganizationMembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'organization' => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
            ],
            'role' => [
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ],
            'is_active' => $this->organization_id === $this->user->active_organization_id,
        ];
    }
}
