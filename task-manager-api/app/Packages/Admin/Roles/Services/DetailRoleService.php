<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\Roles\Models\Role;

class DetailRoleService
{
    public function execute(string $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }
}
