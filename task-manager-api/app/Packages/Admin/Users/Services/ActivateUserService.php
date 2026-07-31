<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivateUserService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $activatedBy): void
    {
        DB::transaction(function () use ($userId, $activatedBy) {
            $user = User::findOrFail($userId);
            $status = UserStatus::where('slug', 'active')->firstOrFail();

            $user->update([
                'last_status_id' => $status->id,
            ]);

            DB::table('admin.user_has_statuses')->insert([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'status_id' => $status->id,
                'reason' => 'Reativado pelo administrador',
                'created_by' => $activatedBy,
                'created_at' => now(),
            ]);

            $this->recordAuditLogService->execute($activatedBy, 'user.activate', 'User', $userId, [], $user->active_organization_id);

            Cache::forget("admin_user_detail_{$userId}");
            $this->clearUserCache($userId);
            $this->bumpCacheVersion('admin_users_list');
        });
    }
}
