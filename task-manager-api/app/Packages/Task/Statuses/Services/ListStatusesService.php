<?php

namespace App\Packages\Task\Statuses\Services;

use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Database\Eloquent\Collection;

class ListStatusesService
{
    public function execute(): Collection
    {
        return TaskStatus::orderBy('slug')->get();
    }
}
