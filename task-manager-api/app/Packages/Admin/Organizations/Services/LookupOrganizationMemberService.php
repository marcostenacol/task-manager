<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Users\Models\User;

class LookupOrganizationMemberService
{
    public function __construct(
        private ResolveTargetOrganizationService $resolveTargetOrganizationService,
    ) {}

    public function execute(string $cpf, string $actorId, ?string $organizationId = null): ?User
    {
        $actor = User::findOrFail($actorId);

        $targetOrganizationId = $this->resolveTargetOrganizationService->execute($actor, $organizationId);

        $target = User::where('cpf', $cpf)->first();

        if (! $target) {
            return null;
        }

        $this->guardAgainstAlreadyMember($actor, $target, $targetOrganizationId);

        return $target;
    }

    private function guardAgainstAlreadyMember(User $actor, User $target, string $organizationId): void
    {
        if ($target->id === $actor->id) {
            throw new \InvalidArgumentException('Esse CPF é o seu próprio.');
        }

        $alreadyMember = $target->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->exists();

        if ($alreadyMember) {
            throw new \InvalidArgumentException('Esse usuário já é membro dessa organization.');
        }
    }
}
