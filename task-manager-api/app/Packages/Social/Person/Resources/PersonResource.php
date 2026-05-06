<?php

namespace App\Packages\Social\Person\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_path' => $this->avatar_path,
            'bio' => $this->bio,
            'role' => $this->whenLoaded('role', fn() => [
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ]),
            'contacts' => \App\Packages\Social\Contacts\Resources\ContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
