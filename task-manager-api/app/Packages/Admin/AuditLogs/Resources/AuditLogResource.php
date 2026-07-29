<?php

namespace App\Packages\Admin\AuditLogs\Resources;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Settings\Models\Setting;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor' => [
                'id' => $this->actor?->id,
                'name' => $this->actor?->name,
            ],
            'target' => [
                'type' => $this->target_type,
                'id' => $this->target_id,
                'name' => $this->resolveTargetName(),
            ],
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveTargetName(): ?string
    {
        if ($this->target_type === 'User') {
            return User::find($this->target_id)?->name;
        }

        if ($this->target_type === 'Role') {
            return Role::find($this->target_id)?->name;
        }

        if ($this->target_type === 'Setting') {
            return Setting::find($this->target_id)?->name;
        }

        return null;
    }
}
