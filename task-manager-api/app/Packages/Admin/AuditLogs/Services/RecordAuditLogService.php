<?php

namespace App\Packages\Admin\AuditLogs\Services;

use App\Packages\Admin\AuditLogs\Models\AuditLog;

class RecordAuditLogService
{
    public function execute(string $actorId, string $action, string $targetType, string $targetId, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
        ]);
    }
}
