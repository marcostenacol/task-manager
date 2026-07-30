<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class AddOrganizationMemberService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private GuardRoleAssignmentService $guardRoleAssignmentService,
    ) {}

    public function execute(string $targetUserId, string $roleId, string $actorId): UserOrganization
    {
        return DB::transaction(function () use ($targetUserId, $roleId, $actorId) {
            $actor = User::findOrFail($actorId);
            $target = User::findOrFail($targetUserId);
            $role = Role::findOrFail($roleId);

            $this->guardActorHasOwnOrganization($actor);
            $this->guardRoleAssignmentService->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            if ($role->scope !== 'organization') {
                throw new \InvalidArgumentException('Só é possível adicionar membros com uma role de organization.');
            }

            $membership = UserOrganization::create([
                'user_id' => $target->id,
                'organization_id' => $actor->active_organization_id,
                'role_id' => $role->id,
            ]);

            if ($target->active_organization_id === null) {
                $target->update(['active_organization_id' => $actor->active_organization_id]);
            }

            $this->recordAuditLogService->execute($actorId, 'organization.member_add', 'User', $target->id, [
                'organization_id' => $actor->active_organization_id,
                'role_id' => $role->id,
            ]);

            return $membership;
        });
    }

    private function guardActorHasOwnOrganization(User $actor): void
    {
        if ($actor->active_organization_id === null) {
            throw new \InvalidArgumentException('Você não pertence a nenhuma organization.');
        }
    }
}
