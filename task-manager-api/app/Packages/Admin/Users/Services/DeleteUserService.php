<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteUserService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $actorId): void
    {
        DB::transaction(function () use ($userId, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $target = User::with('role')->findOrFail($userId);

            $this->guardAgainstSelfDeletion($actor, $target);
            $this->guardAgainstDeletingSuperiorOrEqual($actor, $target);

            $target->delete();

            $this->recordAuditLogService->execute($actorId, 'user.delete', 'User', $userId, [
                'name' => $target->name,
                'email' => $target->email,
            ], $target->active_organization_id);
        });

        Cache::forget("admin_user_detail_{$userId}");
        $this->clearUserCache($userId);
    }

    private function guardAgainstSelfDeletion(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new \InvalidArgumentException('Você não pode excluir a si mesmo.');
        }
    }

    private function guardAgainstDeletingSuperiorOrEqual(User $actor, User $target): void
    {
        if ($target->role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode excluir um usuário com role igual ou superior à sua.');
        }
    }
}
