<?php

namespace App\Packages\Admin\Organizations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMemberLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->maskEmail($this->email),
        ];
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = explode('@', $email, 2);

        $visible = mb_substr($localPart, 0, 2);

        return "{$visible}***@{$domain}";
    }
}
