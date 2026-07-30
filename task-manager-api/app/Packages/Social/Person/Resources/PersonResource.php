<?php

namespace App\Packages\Social\Person\Resources;

use App\Packages\Social\Contacts\Resources\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'avatar_path' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'bio' => $this->bio,
            'cpf' => $this->cpf,
            'status' => $this->whenLoaded('lastStatus', fn () => [
                'name' => $this->lastStatus->name,
                'slug' => $this->lastStatus->slug,
            ]),
            'role' => $this->whenLoaded('role', fn () => [
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ]),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
