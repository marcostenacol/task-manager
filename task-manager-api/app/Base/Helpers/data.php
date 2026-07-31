<?php

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Settings\Models\Setting;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Support\Facades\DB;

function settingsInCache(): mixed
{
    return (new class
    {
        use CacheTrait;
    })->cache(
        key: 'settings',
        callback: function () {
            return Setting::all();
        }
    );
}

function getSetting(string $key): mixed
{
    return (new class
    {
        use CacheTrait;
    })->cache(
        key: 'setting_'.$key,
        callback: function () use ($key) {
            return DB::table('admin.settings')->where('name', $key)->value('value');
        }
    );
}

function taskStatusesInCache(): mixed
{
    return (new class
    {
        use CacheTrait;
    })->cache(
        key: 'task_statuses',
        callback: function () {
            return TaskStatus::all();
        }
    );
}

function taskPrioritiesInCache(): mixed
{
    return (new class
    {
        use CacheTrait;
    })->cache(
        key: 'task_priorities',
        callback: function () {
            return TaskPriority::orderBy('order')->get();
        }
    );
}
