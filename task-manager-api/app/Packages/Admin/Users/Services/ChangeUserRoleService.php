<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChangeUserRoleService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $roleId, string $actorId): void
    {
        DB::transaction(function () use ($userId, $roleId, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $user = User::with('role')->findOrFail($userId);
            $role = Role::findOrFail($roleId);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $user);
            $this->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            $user->update([
                'role_id' => $role->id,
            ]);

            $this->recordAuditLogService->execute($actorId, 'user.role_change', 'User', $userId, [
                'role_id' => $role->id,
                'role_slug' => $role->slug,
            ], $user->active_organization_id);
        });

        Cache::forget("admin_user_detail_{$userId}");
        $this->clearUserCache($userId);
    }

    private function guardAgainstEditingSuperiorOrEqual(User $actor, User $user): void
    {
        if ($user->id === $actor->id) {
            throw new \InvalidArgumentException('Você não pode alterar a sua própria role por aqui.');
        }

        if ($user->role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode alterar a role de um usuário igual ou superior ao seu.');
        }
    }

    private function guardAgainstAssigningSuperiorOrEqualRole(User $actor, Role $role): void
    {
        if ($role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode atribuir uma role igual ou superior à sua.');
        }
    }
}
