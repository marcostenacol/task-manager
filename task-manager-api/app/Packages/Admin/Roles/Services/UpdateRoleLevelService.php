<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Services\GuardRoleAssignmentService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRoleLevelService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private GuardRoleAssignmentService $guardRoleAssignmentService,
    ) {}

    public function execute(string $roleId, int $level, string $actorId, ?string $color = null): Role
    {
        return DB::transaction(function () use ($roleId, $level, $actorId, $color) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $role);
            $this->guardAgainstElevatingAboveActor($actor, $level, $role);
            $this->guardAgainstCrossOrganizationAccess($actor, $role);

            $role->update(array_filter([
                'level' => $level,
                'color' => $color,
            ], fn ($value) => $value !== null));

            $this->recordAuditLogService->execute($actorId, 'role.level_update', 'Role', $role->id, [
                'level' => $level,
                'color' => $color,
            ], $role->organization_id);

            return $role;
        });
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

    private function guardAgainstEditingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($role->id === $actor->role_id) {
            throw new \InvalidArgumentException('Você não pode editar o nível da sua própria role por aqui.');
        }

        if ($this->guardRoleAssignmentService->isRoleSuperiorOrEqual($actor, $role)) {
            throw new \InvalidArgumentException('Você não pode editar uma role igual ou superior à sua.');
        }
    }

    private function guardAgainstElevatingAboveActor(User $actor, int $level, Role $role): void
    {
        $actorIsGlobal = $actor->global_role_id !== null;

        if ($actorIsGlobal && $role->scope === 'organization') {
            return;
        }

        if ($level <= $this->guardRoleAssignmentService->resolveActorLevel($actor)) {
            throw new \InvalidArgumentException('Você não pode definir um nível igual ou superior ao da sua própria role.');
        }
    }
}
