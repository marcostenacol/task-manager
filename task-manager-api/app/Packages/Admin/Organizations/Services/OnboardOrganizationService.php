<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardOrganizationService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, string $actorId): Organization
    {
        return DB::transaction(function () use ($name, $actorId) {
            $actor = User::findOrFail($actorId);

            $this->guardAgainstUserAlreadyInAnOrganization($actor);

            $orgAdminRole = Role::where('slug', 'org-admin')->firstOrFail();

            $organization = Organization::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(6),
            ]);

            UserOrganization::create([
                'user_id' => $actor->id,
                'organization_id' => $organization->id,
                'role_id' => $orgAdminRole->id,
            ]);

            $actor->update(['active_organization_id' => $organization->id]);

            $this->recordAuditLogService->execute($actorId, 'organization.create', 'Organization', $organization->id, [
                'name' => $organization->name,
            ]);

            return $organization;
        });
    }

    private function guardAgainstUserAlreadyInAnOrganization(User $actor): void
    {
        if ($actor->global_role_id !== null) {
            throw new \InvalidArgumentException('Usuários com role global não precisam fundar uma organization.');
        }

        if ($actor->active_organization_id !== null) {
            throw new \InvalidArgumentException('Você já pertence a uma organization.');
        }
    }
}
