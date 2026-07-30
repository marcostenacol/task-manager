<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, ?string $parentId, string $actorId): Organization
    {
        return DB::transaction(function () use ($name, $parentId, $actorId) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(6),
                'parent_id' => $parentId,
            ]);

            $this->recordAuditLogService->execute($actorId, 'organization.create', 'Organization', $organization->id, [
                'name' => $organization->name,
                'parent_id' => $parentId,
            ]);

            return $organization;
        });
    }
}
