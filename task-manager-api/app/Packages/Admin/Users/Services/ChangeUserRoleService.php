<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChangeUserRoleService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $roleId, string $actorId): void
    {
        DB::transaction(function () use ($userId, $roleId, $actorId) {
            $user = User::findOrFail($userId);
            $role = Role::findOrFail($roleId);

            $user->update([
                'role_id' => $role->id,
            ]);

            $this->recordAuditLogService->execute($actorId, 'user.role_change', 'User', $userId, [
                'role_id' => $role->id,
                'role_slug' => $role->slug,
            ]);
        });

        Cache::forget("admin_user_detail_{$userId}");
        $this->clearUserCache($userId);
    }
}
