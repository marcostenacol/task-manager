<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class ListRolesService
{
    public function execute(): Collection
    {
        return Role::withCount('permissions')->orderBy('name')->get();
    }
}
