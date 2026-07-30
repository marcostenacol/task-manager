<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRoleNameService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $roleId, string $name, string $actorId): Role
    {
        return DB::transaction(function () use ($roleId, $name, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $role);
            $this->guardAgainstCrossOrganizationAccess($actor, $role);

            $oldName = $role->name;

            // slug é o identificador estável usado em lookups hardcoded (ex.:
            // Role::where('slug', 'org-admin') em OnboardOrganizationService) —
            // nunca deve mudar após a criação, mesmo quando o nome de exibição muda.
            $role->update(['name' => $name]);

            $this->recordAuditLogService->execute($actorId, 'role.update', 'Role', $role->id, [
                'old_name' => $oldName,
                'new_name' => $name,
            ], $role->organization_id);

            return $role;
        });
    }

    private function guardAgainstEditingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($role->id === $actor->role_id) {
            throw new \InvalidArgumentException('Você não pode renomear a sua própria role por aqui.');
        }

        if ($role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode editar uma role igual ou superior à sua.');
        }
    }

    private function guardAgainstCrossOrganizationAccess(User $actor, Role $role): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        if ($role->organization_id === null || $role->organization_id !== $actor->active_organization_id) {
            throw new \InvalidArgumentException('Você só pode editar roles da própria organization.');
        }
    }
}
