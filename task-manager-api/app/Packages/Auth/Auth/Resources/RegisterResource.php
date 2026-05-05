<?php

namespace App\Packages\Auth\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_slug' => $this->role->slug ?? null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
