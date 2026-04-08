<?php


use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Service\Models\Setting;
use App\Packages\Attendance\AttendanceStatus\Models\AttendanceStatus;
use App\Packages\CollectiveAttendance\CollectiveMeetingStatus\Models\CollectiveMeetingStatus;

function settingsInCache(): mixed {
    return Cache::remember(
        key: 'settings',
        ttl: config('api.cache.ttl'),
        callback: function () {
            return Setting::all();
        }
    );
}

/**
 * @return mixed
 */
function collectiveMeetingStatusInCache(): mixed {
    return (new class {use CacheTrait;})->cache(
        key: 'collective-meeting',
        callback: function () {
            return CollectiveMeetingStatus::orderBy('name')->get();
        }
    );
}



