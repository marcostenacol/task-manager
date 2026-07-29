<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\DB;

class CreateUserService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(array $data, string $actorId): User
    {
        return DB::transaction(function () use ($data, $actorId) {
            $actor = User::findOrFail($actorId);
            $role = Role::findOrFail($data['role_id']);
            $activeStatus = UserStatus::where('slug', 'active')->firstOrFail();

            $this->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            $organizationId = $this->resolveOrganizationId($actor, $role, $data);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $data['role_id'],
                'global_role_id' => $role->scope === 'global' ? $role->id : null,
                'active_organization_id' => $organizationId,
                'last_status_id' => $activeStatus->id,
            ]);

            if ($role->scope === 'organization') {
                UserOrganization::create([
                    'user_id' => $user->id,
                    'organization_id' => $organizationId,
                    'role_id' => $role->id,
                ]);
            }

            $this->recordAuditLogService->execute($actorId, 'user.create', 'User', $user->id, [
                'name' => $user->name,
                'email' => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * `level` só é comparável dentro do mesmo scope (global vs organization
     * são hierarquias separadas — ver docs/organizations-hierarchy-design.md).
     * Um ator global sempre pode atribuir uma role de organization (é
     * inerentemente subordinada); um ator de organization nunca pode
     * atribuir uma role global (evita escalada de privilégio); entre roles
     * do mesmo scope, vale a comparação normal de level.
     */
    private function guardAgainstAssigningSuperiorOrEqualRole(User $actor, Role $role): void
    {
        $actorIsGlobal = $actor->global_role_id !== null;

        if ($actorIsGlobal && $role->scope === 'organization') {
            return;
        }

        if (! $actorIsGlobal && $role->scope === 'global') {
            throw new \InvalidArgumentException('Você não pode atribuir uma role global.');
        }

        if ($role->level <= $this->resolveActorLevel($actor)) {
            throw new \InvalidArgumentException('Você não pode atribuir uma role igual ou superior à sua.');
        }
    }

    private function resolveActorLevel(User $actor): int
    {
        if ($actor->global_role_id !== null) {
            return Role::findOrFail($actor->global_role_id)->level;
        }

        if ($actor->active_organization_id !== null) {
            $membership = UserOrganization::where('user_id', $actor->id)
                ->where('organization_id', $actor->active_organization_id)
                ->first();

            if ($membership) {
                return Role::findOrFail($membership->role_id)->level;
            }
        }

        return Role::findOrFail($actor->role_id)->level;
    }

    private function resolveOrganizationId(User $actor, Role $role, array $data): ?string
    {
        if ($role->scope === 'global') {
            return null;
        }

        if ($actor->global_role_id === null) {
            if ($actor->active_organization_id === null) {
                throw new \InvalidArgumentException('Você não pertence a nenhuma organization para criar usuários.');
            }

            return $actor->active_organization_id;
        }

        return $data['organization_id'] ?? $actor->active_organization_id
            ?? throw new \InvalidArgumentException('Informe a organization do novo usuário.');
    }
}
