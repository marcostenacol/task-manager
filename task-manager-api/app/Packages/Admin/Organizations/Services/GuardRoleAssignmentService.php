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

        if (! $actorIsGlobal && $role->scope === 'global') {
            throw new \InvalidArgumentException('Você não pode atribuir uma role global.');
        }

        if ($this->isRoleSuperiorOrEqual($actor, $role)) {
            throw new \InvalidArgumentException('Você não pode atribuir uma role igual ou superior à sua.');
        }
    }

    /**
     * Compara a hierarquia entre a role e o ator. Um ator global é sempre
     * inferior/igual a uma role global de nível maior/igual, mas nunca a uma
     * role de organization (que é inerentemente subordinada a qualquer role
     * global) — mesma regra de `guardAgainstAssigningSuperiorOrEqualRole`,
     * exposta separadamente para reuso nos Services de edição de Role
     * (rename/level/permissions/delete), que precisam da checagem sem o
     * `throw` amarrado à mensagem de "atribuição".
     */
    public function isRoleSuperiorOrEqual(User $actor, Role $role): bool
    {
        $actorIsGlobal = $actor->global_role_id !== null;

        if ($actorIsGlobal && $role->scope === 'organization') {
            return false;
        }

        return $role->level <= $this->resolveActorLevel($actor);
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
