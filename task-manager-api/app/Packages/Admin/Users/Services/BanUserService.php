<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BanUserService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $reason, string $bannedBy): void
    {
        DB::transaction(function () use ($userId, $reason, $bannedBy) {
            if ($userId === $bannedBy) {
                throw new \InvalidArgumentException('Você não pode banir a si mesmo.');
            }

            $user = User::findOrFail($userId);
            $status = UserStatus::where('slug', 'banned')->firstOrFail();

            // Update user's last status
            $user->update([
                'last_status_id' => $status->id,
            ]);

            // Record in history
            DB::table('admin.user_has_statuses')->insert([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'status_id' => $status->id,
                'reason' => $reason,
                'created_by' => $bannedBy,
                'created_at' => now(),
            ]);

            $this->recordAuditLogService->execute($bannedBy, 'user.ban', 'User', $userId, [
                'reason' => $reason,
            ], $user->active_organization_id);

            Cache::forget("admin_user_detail_{$userId}");
            $this->clearUserCache($userId);
            $this->bumpCacheVersion('admin_users_list');
        });
    }
}
