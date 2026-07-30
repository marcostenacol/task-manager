<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $organizationId, string $name, string $actorId): Organization
    {
        return DB::transaction(function () use ($organizationId, $name, $actorId) {
            $actor = User::findOrFail($actorId);
            $organization = Organization::findOrFail($organizationId);

            $this->guardActorCanManageOrganization($actor, $organization);

            $oldName = $organization->name;

            $organization->update(['name' => $name]);

            $this->recordAuditLogService->execute($actorId, 'organization.update', 'Organization', $organization->id, [
                'old_name' => $oldName,
                'new_name' => $name,
            ]);

            return $organization;
        });
    }

    private function guardActorCanManageOrganization(User $actor, Organization $organization): void
    {
        if ($actor->global_role_id !== null) {
            return;
        }

        if ($actor->active_organization_id !== $organization->id) {
            throw new \InvalidArgumentException('Você só pode editar a sua própria organization.');
        }
    }
}
