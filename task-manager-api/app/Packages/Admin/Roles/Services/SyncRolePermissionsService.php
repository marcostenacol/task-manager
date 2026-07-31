<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Services\GuardRoleAssignmentService;
use App\Packages\Admin\Permissions\Models\Permission;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class SyncRolePermissionsService
{
    /**
     * Permissões que afetam a plataforma inteira e nunca podem ser atribuídas
     * por um ator de escopo organization, mesmo a uma role da própria organization.
     * Mantida em sincronia com $globalOnlyPermissions em database/seeders/PermissionSeeder.php.
     */
    private const GLOBAL_ONLY_PERMISSIONS = ['admin.settings.manage', 'admin.organizations.list'];

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private GuardRoleAssignmentService $guardRoleAssignmentService,
    ) {}

    public function execute(string $roleId, array $permissionIds, string $actorId): Role
    {
        return DB::transaction(function () use ($roleId, $permissionIds, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $role);
            $this->guardAgainstCrossOrganizationAccess($actor, $role);
            $this->guardAgainstAssigningGlobalOnlyPermissions($actor, $permissionIds);

            $role->permissions()->sync($permissionIds);

            $this->recordAuditLogService->execute($actorId, 'role.permissions_update', 'Role', $role->id, [
                'permission_ids' => $permissionIds,
            ], $role->organization_id);

            return $role->load('permissions');
        });
    }

    private function guardAgainstEditingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($role->id === $actor->role_id) {
            throw new \InvalidArgumentException('Você não pode editar as permissões da sua própria role por aqui.');
        }

        if ($this->guardRoleAssignmentService->isRoleSuperiorOrEqual($actor, $role)) {
            throw new \InvalidArgumentException('Você não pode editar uma role igual ou superior à sua.');
        }
    }

    private function guardAgainstCrossOrganizationAccess(User $actor, Role $role): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        if ($role->organization_id === null || $role->organization_id !== $actor->active_organization_id) {
            throw new \InvalidArgumentException('Você só pode editar permissões de roles da própria organization.');
        }
    }

    private function guardAgainstAssigningGlobalOnlyPermissions(User $actor, array $permissionIds): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        $hasGlobalOnlyPermission = Permission::whereIn('id', $permissionIds)
            ->whereIn('name', self::GLOBAL_ONLY_PERMISSIONS)
            ->exists();

        throw_if($hasGlobalOnlyPermission, new \InvalidArgumentException('Você não pode atribuir uma permissão de escopo global.'));
    }
}
