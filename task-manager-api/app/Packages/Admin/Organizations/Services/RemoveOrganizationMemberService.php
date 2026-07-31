<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RemoveOrganizationMemberService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
        private GuardRoleAssignmentService $guard_role_assignment_service,
        private ResolveTargetOrganizationService $resolve_target_organization_service,
    ) {}

    public function execute(string $target_user_id, string $actor_id, ?string $organization_id = null): void
    {
        DB::transaction(function () use ($target_user_id, $actor_id, $organization_id) {
            $actor = User::findOrFail($actor_id);

            $target_organization_id = $this->resolve_target_organization_service->execute($actor, $organization_id);

            $this->guardAgainstRemovingSelf($actor, $target_user_id);

            $membership = UserOrganization::where('organization_id', $target_organization_id)
                ->where('user_id', $target_user_id)
                ->firstOrFail();

            $target_role = $membership->role;

            $this->guard_role_assignment_service->guardAgainstAssigningSuperiorOrEqualRole($actor, $target_role);

            $membership->delete();

            $target = User::find($target_user_id);
            if ($target && $target->active_organization_id === $target_organization_id) {
                $target->update(['active_organization_id' => null]);
            }

            $this->record_audit_log_service->execute($actor_id, 'organization.member_remove', 'User', $target_user_id, [
                'role_id' => $target_role->id,
            ], $target_organization_id);

            $this->clearUserCache($target_user_id);
            Cache::forget("admin_user_detail_{$target_user_id}");
            $this->bumpCacheVersion('admin_users_list');
        });
    }

    private function guardAgainstRemovingSelf(User $actor, string $target_user_id): void
    {
        throw_if($actor->id === $target_user_id, new \InvalidArgumentException('Você não pode remover a si mesmo por aqui.'));
    }
}
