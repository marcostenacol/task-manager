<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteRoleService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $roleId, string $actorId): void
    {
        DB::transaction(function () use ($roleId, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstDeletingOwnRole($actor, $role);
            $this->guardAgainstDeletingSuperiorOrEqual($actor, $role);

            $role->delete();

            $this->recordAuditLogService->execute($actorId, 'role.delete', 'Role', $roleId, [
                'name' => $role->name,
            ]);
        });
    }

    private function guardAgainstDeletingOwnRole(User $actor, Role $role): void
    {
        if ($actor->role_id === $role->id) {
            throw new \InvalidArgumentException('Você não pode excluir a sua própria role.');
        }
    }

    private function guardAgainstDeletingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode excluir uma role igual ou superior à sua.');
        }
    }
}
