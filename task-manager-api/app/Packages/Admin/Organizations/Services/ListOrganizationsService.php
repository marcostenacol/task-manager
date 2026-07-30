<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

class ListOrganizationsService
{
    public function execute(): Collection
    {
        return Organization::withCount('memberships')->orderBy('name')->get();
    }
}
