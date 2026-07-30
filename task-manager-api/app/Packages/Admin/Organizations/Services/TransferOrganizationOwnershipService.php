<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class TransferOrganizationOwnershipService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private ResolveTargetOrganizationService $resolveTargetOrganizationService,
    ) {}

    public function execute(string $newOwnerUserId, string $actorId, ?string $organizationId = null): void
    {
        DB::transaction(function () use ($newOwnerUserId, $actorId, $organizationId) {
            $actor = User::with('role')->findOrFail($actorId);
            $targetOrganizationId = $this->resolveTargetOrganizationService->execute($actor, $organizationId);

            $this->guardAgainstTransferringToSelf($actor, $newOwnerUserId);

            $orgAdminRole = Role::where('slug', 'org-admin')->firstOrFail();
            $userRole = Role::where('slug', 'user')->firstOrFail();

            $this->guardActorIsCurrentOwner($actor, $targetOrganizationId, $orgAdminRole->id);

            $newOwnerMembership = UserOrganization::where('organization_id', $targetOrganizationId)
                ->where('user_id', $newOwnerUserId)
                ->firstOrFail();

            $newOwnerMembership->update(['role_id' => $orgAdminRole->id]);

            $this->demoteCurrentOwner($actor, $targetOrganizationId, $userRole->id);

            $this->recordAuditLogService->execute($actorId, 'organization.ownership_transfer', 'Organization', $targetOrganizationId, [
                'new_owner_user_id' => $newOwnerUserId,
            ], $targetOrganizationId);
        });
    }

    private function guardAgainstTransferringToSelf(User $actor, string $newOwnerUserId): void
    {
        if ($actor->id === $newOwnerUserId) {
            throw new \InvalidArgumentException('Você já é o titular desta organization.');
        }
    }

    private function guardActorIsCurrentOwner(User $actor, string $organizationId, string $orgAdminRoleId): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        $isCurrentOwner = UserOrganization::where('organization_id', $organizationId)
            ->where('user_id', $actor->id)
            ->where('role_id', $orgAdminRoleId)
            ->exists();

        throw_unless($isCurrentOwner, new \InvalidArgumentException('Apenas o titular atual da organization pode transferi-la.'));
    }

    private function demoteCurrentOwner(User $actor, string $organizationId, string $userRoleId): void
    {
        UserOrganization::where('organization_id', $organizationId)
            ->where('user_id', $actor->id)
            ->update(['role_id' => $userRoleId]);
    }
}
