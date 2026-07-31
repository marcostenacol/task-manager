<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Organizations\Resources\OrganizationMemberResource;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ListOrganizationMembersService
{
    public function execute(string $organization_id, string $actor_id, array $filters = []): LengthAwarePaginator
    {
        Organization::findOrFail($organization_id);

        $this->guardAgainstCrossOrganizationAccess($actor_id, $organization_id);

        $paginator = UserOrganization::with(['user', 'role'])
            ->where('organization_id', $organization_id)
            ->paginate((int) ($filters['limit'] ?? 15));

        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($item) => new OrganizationMemberResource($item))
        );

        return $paginator;
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
