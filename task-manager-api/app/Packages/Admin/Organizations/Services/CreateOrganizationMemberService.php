<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\Users\Services\CreateUserService;

class CreateOrganizationMemberService
{
    public function __construct(
        private CreateUserService $createUserService,
    ) {}

    public function execute(array $data, string $actorId): User
    {
        $role = Role::findOrFail($data['role_id']);

        throw_if($role->scope !== 'organization', new \InvalidArgumentException('A role informada não é uma role de organization.'));

        return $this->createUserService->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'password' => $data['cpf'],
            'role_id' => $data['role_id'],
            'organization_id' => $data['organization_id'] ?? null,
        ], $actorId);
    }
}
