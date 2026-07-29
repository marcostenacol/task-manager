<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResetUserPasswordService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $userId, string $password, string $actorId): void
    {
        DB::transaction(function () use ($userId, $password, $actorId) {
            $actor = User::with('role')->findOrFail($actorId);
            $user = User::with('role')->findOrFail($userId);

            $this->guardAgainstResettingSuperiorOrEqual($actor, $user);

            $user->update(['password' => $password]);

            $this->recordAuditLogService->execute($actorId, 'user.password_reset', 'User', $user->id);
        });

        Cache::forget("admin_user_detail_{$userId}");
        $this->clearUserCache($userId);
    }

    private function guardAgainstResettingSuperiorOrEqual(User $actor, User $user): void
    {
        if ($user->id === $actor->id) {
            throw new \InvalidArgumentException('Use a troca de senha do seu próprio perfil para isso.');
        }

        if ($user->role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode redefinir a senha de um usuário com role igual ou superior à sua.');
        }
    }
}
