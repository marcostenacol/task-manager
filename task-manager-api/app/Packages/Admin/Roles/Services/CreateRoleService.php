<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRoleService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, string $actorId, ?string $color = null): Role
    {
        return DB::transaction(function () use ($name, $actorId, $color) {
            $actor = User::with('role')->findOrFail($actorId);

            $organizationId = $this->resolveOrganizationId($actor);

            $role = Role::create([
                'name' => $name,
                'slug' => $this->buildSlug($name, $organizationId),
                'level' => $actor->role->level + 1,
                'color' => $color ?? '#64748b',
                'scope' => 'organization',
                'organization_id' => $organizationId,
            ]);

            $this->recordAuditLogService->execute($actorId, 'role.create', 'Role', $role->id, [
                'name' => $role->name,
            ], $organizationId);

            return $role;
        });
    }

    private function resolveOrganizationId(User $actor): ?string
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return null;
        }

        throw_unless($actor->active_organization_id, new \InvalidArgumentException('Você não pertence a nenhuma organization para criar roles.'));

        return $actor->active_organization_id;
    }

    private function buildSlug(string $name, ?string $organizationId): string
    {
        if (! $organizationId) {
            return Str::slug($name);
        }

        return Str::slug($name).'-'.Str::random(6);
    }
}
