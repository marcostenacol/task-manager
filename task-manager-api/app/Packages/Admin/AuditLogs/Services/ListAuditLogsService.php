<?php

namespace App\Packages\Admin\AuditLogs\Services;

use App\Packages\Admin\AuditLogs\Models\AuditLog;
use App\Packages\Admin\AuditLogs\Resources\AuditLogResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListAuditLogsService
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $limit = (int) ($filters['limit'] ?? 15);

        $query = AuditLog::with('actor')->orderBy('created_at', 'desc');

        $this->applyFilters($query, $filters);

        $paginator = $query->paginate($limit);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($log) => new AuditLogResource($log))
        );

        return $paginator;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['actor_name'])) {
            $query->whereHas('actor', function (Builder $actorQuery) use ($filters) {
                $actorQuery->where('name', 'ILIKE', "%{$filters['actor_name']}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
