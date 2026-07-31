<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\Organizations\Services\ResolveOrganizationScopeService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListRolesService
{
    public function __construct(
        private ResolveOrganizationScopeService $resolveOrganizationScopeService,
    ) {}

    public function execute(string $actorId, ?string $scope = null, ?string $organizationId = null): Collection
    {
        $actor = User::with('role')->findOrFail($actorId);
        $actorIsGlobal = $actor->global_role_id !== null || $actor->role->scope === 'global';

        $query = Role::withCount('permissions')->with('organization')->orderBy('name');

        if (! $actorIsGlobal) {
            $organizationIds = $this->resolveOrganizationScopeService->execute($actorId);

            return $query->where('scope', '!=', 'global')
                ->where(function ($subQuery) use ($organizationIds) {
                    $subQuery->whereNull('organization_id')
                        ->orWhereIn('organization_id', $organizationIds ?? []);
                })
                ->get();
        }

        if ($scope === 'global') {
            $query->where('scope', 'global');
        }

        if ($organizationId !== null) {
            $query->where('scope', '!=', 'global')
                ->where(function ($subQuery) use ($organizationId) {
                    $subQuery->whereNull('organization_id')
                        ->orWhere('organization_id', $organizationId);
                });
        }

        return $query->get();
    }
}
