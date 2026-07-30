<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;

class GuardRoleAssignmentService
{
    /**
     * `level` só é comparável dentro do mesmo scope (global vs organization
     * são hierarquias separadas — ver docs/organizations-hierarchy-design.md).
     * Um ator global sempre pode atribuir uma role de organization (é
     * inerentemente subordinada); um ator de organization nunca pode
     * atribuir uma role global (evita escalada de privilégio); entre roles
     * do mesmo scope, vale a comparação normal de level.
     */
    public function guardAgainstAssigningSuperiorOrEqualRole(User $actor, Role $role): void
    {
        $actorIsGlobal = $actor->global_role_id !== null;

        if ($actorIsGlobal && $role->scope === 'organization') {
            return;
        }

        if (! $actorIsGlobal && $role->scope === 'global') {
            throw new \InvalidArgumentException('Você não pode atribuir uma role global.');
        }

        if ($role->level <= $this->resolveActorLevel($actor)) {
            throw new \InvalidArgumentException('Você não pode atribuir uma role igual ou superior à sua.');
        }
    }

    public function resolveActorLevel(User $actor): int
    {
        if ($actor->global_role_id !== null) {
            return Role::findOrFail($actor->global_role_id)->level;
        }

        if ($actor->active_organization_id !== null) {
            $membership = UserOrganization::where('user_id', $actor->id)
                ->where('organization_id', $actor->active_organization_id)
                ->first();

            if ($membership) {
                return Role::findOrFail($membership->role_id)->level;
            }
        }

        return Role::findOrFail($actor->role_id)->level;
    }
}
