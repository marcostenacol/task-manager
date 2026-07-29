<?php

namespace App\Packages\Admin\Permissions\Services;

use App\Packages\Admin\Permissions\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class ListPermissionsService
{
    public function execute(): Collection
    {
        return Permission::orderBy('name')->get();
    }
}
