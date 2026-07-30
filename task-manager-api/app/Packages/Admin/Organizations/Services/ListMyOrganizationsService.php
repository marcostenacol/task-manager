<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Organizations\Models\UserOrganization;
use Illuminate\Database\Eloquent\Collection;

class ListMyOrganizationsService
{
    public function execute(string $actorId): Collection
    {
        return UserOrganization::with(['organization', 'role', 'user'])
            ->where('user_id', $actorId)
            ->get();
    }
}
