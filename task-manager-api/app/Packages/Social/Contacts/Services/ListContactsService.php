<?php

namespace App\Packages\Social\Contacts\Services;

use App\Packages\Social\Contacts\Models\UserContact;
use Illuminate\Database\Eloquent\Collection;

class ListContactsService
{
    public function execute(string $userId): Collection
    {
        return UserContact::where('user_id', $userId)->get();
    }
}
