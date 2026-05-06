<?php

namespace App\Packages\Social\Contacts\Services;

use App\Packages\Social\Contacts\Models\UserContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateContactsService
{
    public function execute(string $userId, array $contacts): void
    {
        DB::transaction(function () use ($userId, $contacts) {
            // Remove contatos que não estão na lista (opcional, dependendo do design)
            // Aqui vamos assumir que queremos sincronizar a lista completa
            UserContact::where('user_id', $userId)->delete();

            foreach ($contacts as $contact) {
                UserContact::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'type' => $contact['type'],
                    'value' => $contact['value'],
                    'is_primary' => $contact['is_primary'] ?? false,
                ]);
            }
        });
    }
}
