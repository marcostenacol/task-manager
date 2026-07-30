<?php

namespace App\Packages\Auth\Auth\Services;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\Users\Repositories\UserRepository;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\DB;

class RegisterService
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roleUser = Role::where('slug', 'user')->firstOrFail();
            $statusActive = UserStatus::where('slug', 'active')->firstOrFail();

            return app(UserRepository::class)->save([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'cpf' => $data['cpf'] ?? null,
                'role_id' => $roleUser->id,
                'last_status_id' => $statusActive->id,
            ]);
        });
    }
}
