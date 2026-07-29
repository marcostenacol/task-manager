<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Facades\DB;

class CreateUserService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(array $data, string $actorId): User
    {
        return DB::transaction(function () use ($data, $actorId) {
            $activeStatus = UserStatus::where('slug', 'active')->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $data['role_id'],
                'last_status_id' => $activeStatus->id,
            ]);

            $this->recordAuditLogService->execute($actorId, 'user.create', 'User', $user->id, [
                'name' => $user->name,
                'email' => $user->email,
            ]);

            return $user;
        });
    }
}
