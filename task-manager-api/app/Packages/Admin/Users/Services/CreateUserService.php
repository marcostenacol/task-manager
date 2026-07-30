<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Organizations\Services\GuardRoleAssignmentService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\DB;

class CreateUserService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private GuardRoleAssignmentService $guardRoleAssignmentService,
    ) {}

    public function execute(array $data, string $actorId): User
    {
        return DB::transaction(function () use ($data, $actorId) {
            $actor = User::findOrFail($actorId);
            $role = Role::findOrFail($data['role_id']);
            $activeStatus = UserStatus::where('slug', 'active')->firstOrFail();

            $this->guardRoleAssignmentService->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            $organizationId = $this->resolveOrganizationId($actor, $role, $data);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'cpf' => $data['cpf'] ?? null,
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
