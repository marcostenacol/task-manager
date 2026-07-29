<?php

namespace App\Packages\Social\Person\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class ChangePasswordService
{
    use CacheTrait;

    public function execute(string $userId, string $currentPassword, string $newPassword): void
    {
        DB::transaction(function () use ($userId, $currentPassword, $newPassword) {
            $user = User::findOrFail($userId);

            $this->guardAgainstWrongCurrentPassword($userId, $currentPassword);

            $user->update(['password' => $newPassword]);
        });

        $this->clearCache('user_profile_', $userId);
        $this->clearUserCache($userId);
    }

    private function guardAgainstWrongCurrentPassword(string $userId, string $currentPassword): void
    {
        $result = DB::selectOne(
            'SELECT (password = crypt(?, password)) as matches FROM admin.users WHERE id = ?',
            [$currentPassword, $userId]
        );

        if (! $result?->matches) {
            throw new \InvalidArgumentException('Senha atual incorreta.');
        }
    }
}
