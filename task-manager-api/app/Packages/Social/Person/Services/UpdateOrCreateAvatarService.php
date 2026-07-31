<?php

namespace App\Packages\Social\Person\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateOrCreateAvatarService
{
    use CacheTrait;

    public function execute(string $userId, UploadedFile $file): string
    {
        $user = User::findOrFail($userId);

        // Deleta avatar antigo se existir
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Salva novo avatar
        $path = $file->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        // Invalida caches
        $this->clearCache('user_profile_', $userId);
        $this->clearUserCache($userId);

        return Storage::disk('public')->url($path);
    }
}
