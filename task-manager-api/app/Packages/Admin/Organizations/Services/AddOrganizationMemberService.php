<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AddOrganizationMemberService
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
            $target = User::findOrFail($target_user_id);
            $role = Role::findOrFail($role_id);

            $target_organization_id = $this->resolve_target_organization_service->execute($actor, $organization_id);

            $this->guard_role_assignment_service->guardAgainstAssigningSuperiorOrEqualRole($actor, $role);

            if ($role->scope !== 'organization') {
                throw new \InvalidArgumentException('Só é possível adicionar membros com uma role de organization.');
            }

            $membership = UserOrganization::create([
                'user_id' => $target->id,
                'organization_id' => $target_organization_id,
                'role_id' => $role->id,
            ]);

            if ($target->active_organization_id === null) {
                $target->update(['active_organization_id' => $target_organization_id]);
            }

            $this->record_audit_log_service->execute($actor_id, 'organization.member_add', 'User', $target->id, [
                'organization_id' => $target_organization_id,
                'role_id' => $role->id,
            ], $target_organization_id);

            $this->clearUserCache($target_user_id);
            Cache::forget("admin_user_detail_{$target_user_id}");
            $this->bumpCacheVersion('admin_users_list');

            return $membership;
        });
    }
}
