<?php

namespace App\Packages\Social\Contacts\Services;

use App\Packages\Social\Contacts\Models\UserContact;
use App\Packages\Social\Contacts\Resources\ContactResource;
use Illuminate\Support\Facades\DB;

class CreateContactService
{
    public function execute(string $userId, array $data): ContactResource
    {
        return DB::transaction(function () use ($userId, $data) {
            if (data_get($data, 'is_primary')) {
                UserContact::where('user_id', $userId)->update(['is_primary' => false]);
            }

            $contact = UserContact::create(array_merge($data, ['user_id' => $userId]));

            return new ContactResource($contact);
        });
    }
}
