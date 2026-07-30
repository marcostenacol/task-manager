<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;

class DetailRoleService
{
    public function execute(string $id, string $actorId): Role
    {
        $actor = User::with('role')->findOrFail($actorId);
        $role = Role::with('permissions')->findOrFail($id);

        $this->guardAgainstCrossOrganizationAccess($actor, $role);

        return $role;
    }

    private function guardAgainstCrossOrganizationAccess(User $actor, Role $role): void
    {
        if ($actor->global_role_id !== null || $actor->role->scope === 'global') {
            return;
        }

        if ($role->organization_id !== null && $role->organization_id !== $actor->active_organization_id) {
            throw new \InvalidArgumentException('Você só pode ver roles da própria organization.');
        }
    }
}
