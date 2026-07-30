<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class SwitchActiveOrganizationService
{
    use CacheTrait;

    public function execute(string $organizationId, string $actorId): User
    {
        $user = DB::transaction(function () use ($organizationId, $actorId) {
            $actor = User::findOrFail($actorId);

            $this->guardActorCanSwitchToOrganization($actor, $organizationId);

            $actor->update(['active_organization_id' => $organizationId]);

            return $actor;
        });

        $this->clearUserCache($actorId);

        return $user;
    }

    private function guardActorCanSwitchToOrganization(User $actor, string $organizationId): void
    {
        if ($actor->global_role_id !== null) {
            throw new \InvalidArgumentException('Usuários com role global não precisam trocar de organization.');
        }

        $isMember = UserOrganization::where('user_id', $actor->id)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $isMember) {
            throw new \InvalidArgumentException('Você não é membro dessa organization.');
        }
    }
}
