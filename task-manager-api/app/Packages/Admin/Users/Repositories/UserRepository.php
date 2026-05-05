<?php

namespace App\Packages\Admin\Users\Repositories;

use App\Base\Repository\BaseRepository;
use App\Packages\Admin\Users\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
}
