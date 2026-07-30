<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Auth\Auth\Enum\SettingsEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardOrganizationService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, string $actorId): Organization
    {
        return DB::transaction(function () use ($name, $actorId) {
            $actor = User::findOrFail($actorId);

            $orgAdminRole = Role::where('slug', 'org-admin')->firstOrFail();

            $this->guardAgainstGlobalActor($actor);
            $this->guardAgainstFounderLimit($actor, $orgAdminRole->id);

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
            ], $organization->id);

            $this->clearUserCache($actorId);

            return $organization;
        });
    }

    private function guardAgainstGlobalActor(User $actor): void
    {
        if ($actor->global_role_id !== null) {
            throw new \InvalidArgumentException('Usuários com role global já administram todas as organizations.');
        }
    }

    private function guardAgainstFounderLimit(User $actor, string $orgAdminRoleId): void
    {
        $maxActive = (int) SettingsEnum::getValue(SettingsEnum::ORGANIZATION_MAX_ACTIVE_PER_FOUNDER);

        $administeredCount = UserOrganization::where('user_id', $actor->id)
            ->where('role_id', $orgAdminRoleId)
            ->count();

        if ($administeredCount >= $maxActive) {
            throw new \InvalidArgumentException("Você já atingiu o limite de {$maxActive} organizations administradas.");
        }
    }
}
