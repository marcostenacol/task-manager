<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use App\Base\Traits\CacheTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ActivateUserService
{
    use CacheTrait;

    public function execute(string $userId, string $activatedBy): void
    {
        DB::transaction(function () use ($userId, $activatedBy) {
            $user = User::findOrFail($userId);
            $status = UserStatus::where('slug', 'active')->firstOrFail();

            $user->update([
                'last_status_id' => $status->id
            ]);

            DB::table('admin.user_has_statuses')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'status_id' => $status->id,
                'reason' => 'Reativado pelo administrador',
                'created_by' => $activatedBy,
                'created_at' => now(),
            ]);

            Cache::forget("admin_user_detail_{$userId}");
            $this->clearUserCache($userId);
        });
    }
}
