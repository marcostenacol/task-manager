<?php

namespace App\Packages\Social\Contacts\Services;

use App\Packages\Social\Contacts\Models\UserContact;
use Illuminate\Support\Facades\DB;

class DeleteContactService
{
    public function execute(string $userId, string $id): void
    {
        DB::transaction(function () use ($userId, $id) {
            $contact = UserContact::where('user_id', $userId)->findOrFail($id);
            $contact->delete();
        });
    }
}
