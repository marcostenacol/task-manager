<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Services\GuardRoleAssignmentService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteRoleService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private GuardRoleAssignmentService $guardRoleAssignmentService,
    ) {}

    public function execute(string $roleId, string $actorId): void
    {
        DB::transaction(function () use ($roleId, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstDeletingOwnRole($actor, $role);
            $this->guardAgainstDeletingSuperiorOrEqual($actor, $role);
            $this->guardAgainstCrossOrganizationAccess($actor, $role);

            $organizationId = $role->organization_id;

            $role->delete();

            $this->recordAuditLogService->execute($actorId, 'role.delete', 'Role', $roleId, [
                'name' => $role->name,
            ], $organizationId);
        });
    }

    private function guardAgainstCrossOrganizationAccess(User $actor, Role $role): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        if ($role->organization_id === null || $role->organization_id !== $actor->active_organization_id) {
            throw new \InvalidArgumentException('Você só pode excluir roles da própria organization.');
        }
    }

    private function guardAgainstDeletingOwnRole(User $actor, Role $role): void
    {
        if ($actor->role_id === $role->id) {
            throw new \InvalidArgumentException('Você não pode excluir a sua própria role.');
        }
    }

    private function guardAgainstDeletingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($this->guardRoleAssignmentService->isRoleSuperiorOrEqual($actor, $role)) {
            throw new \InvalidArgumentException('Você não pode excluir uma role igual ou superior à sua.');
        }
    }
}
