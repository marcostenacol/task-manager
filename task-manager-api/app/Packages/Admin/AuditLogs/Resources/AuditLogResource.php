<?php

namespace App\Packages\Admin\AuditLogs\Resources;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Permissions\Models\Permission;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Settings\Models\Setting;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Chaves de metadata que guardam um id (ou lista de ids) e o Model usado
     * pra resolver o valor legível correspondente. Metadata é sempre gravada
     * com o id bruto (RecordAuditLogService não conhece o domínio de cada
     * Service chamador) — a tradução pra nome fica concentrada aqui, no único
     * lugar que efetivamente exibe isso pra um humano.
     */
    private const ID_KEY_MODELS = [
        'role_id' => Role::class,
        'old_role_id' => Role::class,
        'new_role_id' => Role::class,
        'organization_id' => Organization::class,
        'parent_id' => Organization::class,
        'new_owner_user_id' => User::class,
        'permission_ids' => Permission::class,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor' => [
                'id' => $this->actor?->id,
                'name' => $this->actor?->name,
            ],
            'organization' => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ],
            'target' => [
                'type' => $this->target_type,
                'id' => $this->target_id,
                'name' => $this->resolveTargetName(),
            ],
            'metadata' => $this->resolveMetadataLabels(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveMetadataLabels(): array
    {
        $metadata = $this->metadata ?? [];

        if (! is_array($metadata) || empty($metadata)) {
            return $metadata;
        }

        $labels = [];

        foreach ($metadata as $key => $value) {
            $labels[$key] = $this->resolveMetadataValue($key, $value);
        }

        return $labels;
    }

    private function resolveMetadataValue(string $key, mixed $value): mixed
    {
        $model_class = self::ID_KEY_MODELS[$key] ?? null;

        if (! $model_class || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return $model_class::whereIn('id', $value)->pluck('name')->all();
        }

        return $model_class::find($value)?->name ?? $value;
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
