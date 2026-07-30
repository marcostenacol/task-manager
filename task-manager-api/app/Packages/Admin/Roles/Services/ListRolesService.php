<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListRolesService
{
    public function execute(string $actorId): Collection
    {
        $actor = User::with('role')->findOrFail($actorId);

        $query = Role::withCount('permissions')->with('organization')->orderBy('name');

        if ($actor->global_role_id === null && $actor->role->scope !== 'global') {
            $query->where('scope', '!=', 'global')
                ->where(function ($subQuery) use ($actor) {
                    $subQuery->whereNull('organization_id')
                        ->orWhere('organization_id', $actor->active_organization_id);
                });
        }

        return $query->get();
    }
}
