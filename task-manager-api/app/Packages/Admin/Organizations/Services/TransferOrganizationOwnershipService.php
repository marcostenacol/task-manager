<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransferOrganizationOwnershipService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
        private ResolveTargetOrganizationService $resolve_target_organization_service,
    ) {}

    public function execute(string $new_owner_user_id, string $actor_id, ?string $organization_id = null): void
    {
        DB::transaction(function () use ($new_owner_user_id, $actor_id, $organization_id) {
            $actor = User::with('role')->findOrFail($actor_id);
            $target_organization_id = $this->resolve_target_organization_service->execute($actor, $organization_id);

            $this->guardAgainstTransferringToSelf($actor, $new_owner_user_id);

            $org_admin_role = Role::where('slug', 'org-admin')->firstOrFail();
            $user_role = Role::where('slug', 'user')->firstOrFail();

            $this->guardActorIsCurrentOwner($actor, $target_organization_id, $org_admin_role->id);

            $new_owner_membership = UserOrganization::where('organization_id', $target_organization_id)
                ->where('user_id', $new_owner_user_id)
                ->firstOrFail();

            $new_owner_membership->update(['role_id' => $org_admin_role->id]);

            $this->demoteCurrentOwner($actor, $target_organization_id, $user_role->id);

            $this->record_audit_log_service->execute($actor_id, 'organization.ownership_transfer', 'Organization', $target_organization_id, [
                'new_owner_user_id' => $new_owner_user_id,
            ], $target_organization_id);

            $this->clearUserCache($new_owner_user_id);
            $this->clearUserCache($actor_id);
            Cache::forget("admin_user_detail_{$new_owner_user_id}");
            Cache::forget("admin_user_detail_{$actor_id}");
            $this->bumpCacheVersion('admin_users_list');
        });
    }

    private function guardAgainstTransferringToSelf(User $actor, string $new_owner_user_id): void
    {
        if ($actor->id === $new_owner_user_id) {
            throw new \InvalidArgumentException('Você já é o titular desta organization.');
        }
    }

    private function guardActorIsCurrentOwner(User $actor, string $organization_id, string $org_admin_role_id): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        $is_current_owner = UserOrganization::where('organization_id', $organization_id)
            ->where('user_id', $actor->id)
            ->where('role_id', $org_admin_role_id)
            ->exists();

        throw_unless($is_current_owner, new \InvalidArgumentException('Apenas o titular atual da organization pode transferi-la.'));
    }

    private function demoteCurrentOwner(User $actor, string $organization_id, string $user_role_id): void
    {
        UserOrganization::where('organization_id', $organization_id)
            ->where('user_id', $actor->id)
            ->update(['role_id' => $user_role_id]);
    }
}
