<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use Illuminate\Database\Eloquent\Collection;

class ListOrganizationMembersService
{
    public function execute(string $organizationId): Collection
    {
        Organization::findOrFail($organizationId);

        return UserOrganization::with(['user', 'role'])
            ->where('organization_id', $organizationId)
            ->get();
    }
}
