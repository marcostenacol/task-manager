<?php

namespace App\Packages\Task\Priorities\Services;

use App\Packages\Task\Priorities\Models\TaskPriority;
use Illuminate\Database\Eloquent\Collection;

class ListPrioritiesService
{
    public function execute(): Collection
    {
        return TaskPriority::orderBy('order')->get();
    }
}
