<?php

namespace App\Packages\Social\Person\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Social\Person\Resources\PersonResource;

class UpdatePersonService
{
    use CacheTrait;

    public function execute(string $userId, array $data): PersonResource
    {
        $user = User::findOrFail($userId);
        $user->update($data);

        // Invalida cache do perfil
        $this->clearCache('user_profile_', $userId);
        // Invalida cache do objeto de usuário (usado no AuthenticateMiddleware)
        $this->clearUserCache($userId);

        return new PersonResource($user->fresh(['role']));
    }
}
