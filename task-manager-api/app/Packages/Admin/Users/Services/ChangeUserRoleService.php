<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\Roles\Models\Role;
use App\Base\Traits\CacheTrait;
use Illuminate\Support\Facades\Cache;

class ChangeUserRoleService
{
    use CacheTrait;

    public function execute(string $userId, string $roleId): void
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        $user->update([
            'role_id' => $role->id
        ]);

        Cache::forget("admin_user_detail_{$userId}");
        $this->clearUserCache($userId);
    }
}
