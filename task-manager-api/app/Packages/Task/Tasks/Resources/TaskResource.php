<?php

namespace App\Packages\Task\Tasks\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'status' => [
                'id' => $this->status_id,
                'name' => $this->status_name ?? $this->status?->name,
                'slug' => $this->status_slug ?? $this->status?->slug,
            ],
            'priority' => [
                'id' => $this->priority_id,
                'name' => $this->priority_name ?? $this->priority?->name,
                'slug' => $this->priority_slug ?? $this->priority?->slug,
                'order' => $this->priority_order ?? $this->priority?->order,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
