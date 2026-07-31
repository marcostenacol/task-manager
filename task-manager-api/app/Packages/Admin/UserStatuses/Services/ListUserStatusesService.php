<?php

namespace App\Packages\Admin\UserStatuses\Services;

use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Database\Eloquent\Collection;

class ListUserStatusesService
{
    public function execute(): Collection
    {
        return UserStatus::orderBy('slug')->get();
    }
}
