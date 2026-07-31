<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationMemberRoleService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
        private GuardRoleAssignmentService $guard_role_assignment_service,
        private ResolveTargetOrganizationService $resolve_target_organization_service,
    ) {}

    public function execute(string $target_user_id, string $role_id, string $actor_id, ?string $organization_id = null): UserOrganization
    {
        return DB::transaction(function () use ($target_user_id, $role_id, $actor_id, $organization_id) {
            $actor = User::findOrFail($actor_id);
            $role = Role::findOrFail($role_id);

            $target_organization_id = $this->resolve_target_organization_service->execute($actor, $organization_id);

            $this->guardAgainstChangingOwnRole($actor, $target_user_id);
            $this->guard_role_assignment_service->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            throw_if($role->scope !== 'organization', new \InvalidArgumentException('Só é possível atribuir uma role de organization.'));

            $membership = UserOrganization::where('organization_id', $target_organization_id)
                ->where('user_id', $target_user_id)
                ->firstOrFail();

            $old_role_id = $membership->role_id;

            $membership->update(['role_id' => $role->id]);

            $this->record_audit_log_service->execute($actor_id, 'organization.member_role_update', 'User', $target_user_id, [
                'old_role_id' => $old_role_id,
                'new_role_id' => $role->id,
            ], $target_organization_id);

            $this->clearUserCache($target_user_id);
            Cache::forget("admin_user_detail_{$target_user_id}");
            $this->bumpCacheVersion('admin_users_list');

            return $membership;
        });
    }

    private function guardAgainstChangingOwnRole(User $actor, string $target_user_id): void
    {
        throw_if($actor->id === $target_user_id, new \InvalidArgumentException('Você não pode alterar a sua própria role por aqui.'));
    }
}
