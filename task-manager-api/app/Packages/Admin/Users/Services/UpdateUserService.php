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
            $actor = User::with('role')->findOrFail($actorId);
            $user = User::with('role')->findOrFail($id);

            $this->guardAgainstEditingSuperiorOrEqual($actor, $user);

            $user->update($data);

            $this->recordAuditLogService->execute($actorId, 'user.update', 'User', $user->id, $data);

            return $user;
        });
    }

    private function guardAgainstEditingSuperiorOrEqual(User $actor, User $user): void
    {
        if ($user->id === $actor->id) {
            throw new \InvalidArgumentException('Você não pode editar a si mesmo por aqui.');
        }

        if ($user->role->level <= $actor->role->level) {
            throw new \InvalidArgumentException('Você não pode editar um usuário com role igual ou superior à sua.');
        }
    }
}
