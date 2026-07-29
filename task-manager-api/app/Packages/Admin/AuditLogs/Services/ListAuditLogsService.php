<?php

namespace App\Packages\Admin\AuditLogs\Services;

use App\Packages\Admin\AuditLogs\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

class ListAuditLogsService
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $limit = (int) ($filters['limit'] ?? 15);

        return AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
