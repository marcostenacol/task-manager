<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListOrganizationMembersService
{
    public function execute(string $organization_id, string $actor_id): Collection
    {
        Organization::findOrFail($organization_id);

        $this->guardAgainstCrossOrganizationAccess($actor_id, $organization_id);

        return UserOrganization::with(['user', 'role'])
            ->where('organization_id', $organization_id)
            ->get();
    }

    private function guardAgainstCrossOrganizationAccess(string $actor_id, string $organization_id): void
    {
        $actor = User::with('role')->findOrFail($actor_id);

        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        throw_unless($actor->active_organization_id === $organization_id, new \InvalidArgumentException('Você só pode ver os membros da própria organization.'));
    }
}
