<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            $oldName = $role->name;

            $role->update([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);

            $this->recordAuditLogService->execute($actorId, 'role.update', 'Role', $role->id, [
                'old_name' => $oldName,
                'new_name' => $name,
            ]);

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
}
