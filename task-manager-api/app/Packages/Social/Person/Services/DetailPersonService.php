<?php

namespace App\Packages\Social\Person\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Social\Person\Resources\PersonResource;

class DetailPersonService
{
    use CacheTrait;

    public function execute(string $userId): PersonResource
    {
        $user = $this->cache(
            key: 'user_profile_'.$userId,
            callback: function () use ($userId) {
                return User::with(['role', 'lastStatus', 'contacts'])->findOrFail($userId);
            }
        );

        return new PersonResource($user);
    }
}
