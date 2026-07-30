<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Users\Models\User;

class ResolveTargetOrganizationService
{
    /**
     * Ator global precisa informar explicitamente qual organization (não tem
     * uma "própria"); ator de organization só pode operar na sua ativa.
     */
    public function execute(User $actor, ?string $requestedOrganizationId): string
    {
        if ($actor->global_role_id !== null) {
            if (! $requestedOrganizationId) {
                throw new \InvalidArgumentException('Informe a organization.');
            }

            return $requestedOrganizationId;
        }

        if ($actor->active_organization_id === null) {
            throw new \InvalidArgumentException('Você não pertence a nenhuma organization.');
        }

        return $actor->active_organization_id;
    }
}
