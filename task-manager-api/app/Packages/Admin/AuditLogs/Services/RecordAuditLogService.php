<?php

namespace App\Packages\Admin\AuditLogs\Services;

use App\Packages\Admin\AuditLogs\Models\AuditLog;

class RecordAuditLogService
{
    public function execute(string $actorId, string $action, string $targetType, string $targetId, array $metadata = [], ?string $organizationId = null): void
    {
        AuditLog::create([
            'actor_id' => $actorId,
            'organization_id' => $organizationId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
        ]);
    }
}
