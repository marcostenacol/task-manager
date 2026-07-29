<?php

namespace App\Packages\Admin\Settings\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateSettingService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(int|string $id, string $value, string $actorId): Setting
    {
        $setting = DB::transaction(function () use ($id, $value, $actorId) {
            $setting = Setting::findOrFail($id);
            $oldValue = $setting->value;

            $setting->update(['value' => $value]);

            $this->recordAuditLogService->execute($actorId, 'setting.update', 'Setting', (string) $setting->id, [
                'name' => $setting->name,
                'old_value' => $oldValue,
                'new_value' => $value,
            ]);

            return $setting;
        });

        Cache::forget('settings');
        Cache::forget("setting_{$setting->name}");

        return $setting;
    }
}
