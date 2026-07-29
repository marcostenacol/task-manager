<?php

namespace App\Packages\Admin\Settings\Services;

use App\Packages\Admin\Settings\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class ListSettingsService
{
    public function execute(): Collection
    {
        return Setting::orderBy('name')->get();
    }
}
