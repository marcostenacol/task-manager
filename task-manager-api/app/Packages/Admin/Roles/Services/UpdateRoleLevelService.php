<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRoleLevelService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $roleId, int $level, string $actorId, ?string $color = null): Role
    {
        return DB::transaction(function () use ($roleId, $level, $actorId, $color) {
            $actor = User::with('role')->findOrFail($actorId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $role);
            $this->guardAgainstElevatingAboveActor($actor, $level);

            $role->update(array_filter([
                'level' => $level,
                'color' => $color,
            ], fn ($value) => $value !== null));

            $this->recordAuditLogService->execute($actorId, 'role.level_update', 'Role', $role->id, [
                'level' => $level,
                'color' => $color,
            ]);

            return $role;
        });
    }

    private function guardAgainstEditingSuperiorOrEqual(User $actor, Role $role): void
    {
        if ($role->id === $actor->role_id) {
            throw new \InvalidArgumentException('Você não pode editar o nível da sua própria role por aqui.');
        }

        if ($role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode editar uma role igual ou superior à sua.');
        }
    }

    private function guardAgainstElevatingAboveActor(User $actor, int $level): void
    {
        if ($level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode definir um nível igual ou superior ao da sua própria role.');
        }
    }
}
