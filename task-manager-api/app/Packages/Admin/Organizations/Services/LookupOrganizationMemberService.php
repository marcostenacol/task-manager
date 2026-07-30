<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Users\Models\User;

class LookupOrganizationMemberService
{
    public function execute(string $cpf, string $actorId): ?User
    {
        $actor = User::findOrFail($actorId);

        $this->guardActorHasOwnOrganization($actor);

        $target = User::where('cpf', $cpf)->first();

        if (! $target) {
            return null;
        }

        $this->guardAgainstAlreadyMember($actor, $target);

        return $target;
    }

    private function guardActorHasOwnOrganization(User $actor): void
    {
        if ($actor->global_role_id === null && $actor->active_organization_id === null) {
            throw new \InvalidArgumentException('Você não pertence a nenhuma organization.');
        }
    }

    private function guardAgainstAlreadyMember(User $actor, User $target): void
    {
        if ($target->id === $actor->id) {
            throw new \InvalidArgumentException('Esse CPF é o seu próprio.');
        }

        $alreadyMember = $target->organizationMemberships()
            ->where('organization_id', $actor->active_organization_id)
            ->exists();

        if ($alreadyMember) {
            throw new \InvalidArgumentException('Esse usuário já é membro da sua organization.');
        }
    }
}
