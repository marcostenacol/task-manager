<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $id, array $data, string $actorId): User
    {
        return DB::transaction(function () use ($id, $data, $actorId) {
            $user = User::findOrFail($id);
            $user->update($data);

            $this->recordAuditLogService->execute($actorId, 'user.update', 'User', $user->id, $data);

            return $user;
        });
    }
}
