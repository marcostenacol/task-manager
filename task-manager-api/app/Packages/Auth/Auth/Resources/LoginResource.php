<?php

namespace App\Packages\Auth\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource->access_token ?? null,
            'refresh_token' => $this->resource->refresh_token ?? null,
            'user' => $this->resource->user ?? null,
        ];
    }
}
