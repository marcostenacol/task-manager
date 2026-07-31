<?php

namespace App\Packages\Task\Tasks\Repositories;

use App\Base\Repository\BaseRepository;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskRepository extends BaseRepository
{
    public function __construct(Task $model)
    {
        $this->model = $model;
    }

    /**
     * Lista tarefas com filtros usando CTE para melhor performance.
     *
     * Visibilidade: sempre inclui as tasks pessoais do próprio ator. Além
     * disso, tasks com visibility='organization' entram se: o ator for global
     * (vê de qualquer organization) ou o ator pertencer à mesma organization
     * da task. $organization_id/$actor_is_global vêm de ListTasksService, que
     * já resolve o escopo do ator antes de chamar este método.
     */
    public function listWithFilters(string $user_id, array $filters = [], ?string $organization_id = null, bool $actor_is_global = false): LengthAwarePaginator
    {
        $limit = $filters['limit'] ?? 15;
        $search = $filters['search'] ?? null;
        $status_id = $filters['status_id'] ?? null;
        $priority_id = $filters['priority_id'] ?? null;
        $due_date = $filters['due_date'] ?? null;
        $filter_organization_id = $actor_is_global ? ($filters['organization_id'] ?? null) : null;

        $visibility_condition = $this->buildVisibilityCondition($actor_is_global, $organization_id, $filter_organization_id);

        $query = '
            WITH tasks_filtered AS (
                SELECT
                    T.*,
                    S.name as status_name,
                    S.slug as status_slug,
                    P.name as priority_name,
                    P.slug as priority_slug,
                    P.order as priority_order
                FROM public.tasks T
                JOIN public.task_statuses S ON T.status_id = S.id
                JOIN public.task_priorities P ON T.priority_id = P.id
                WHERE ('.$visibility_condition.')
                AND T.deleted_at IS NULL
                '.($search ? 'AND (T.title ILIKE :search OR T.description ILIKE :search)' : '').'
                '.($status_id ? 'AND T.status_id = :status_id' : '').'
                '.($priority_id ? 'AND T.priority_id = :priority_id' : '').'
                '.($due_date ? 'AND T.due_date::date = :due_date::date' : '').'
            )
            SELECT * FROM tasks_filtered
            ORDER BY priority_order DESC, due_date ASC NULLS LAST, created_at DESC
        ';

        $params = ['user_id' => $user_id];
        if (! $actor_is_global && $organization_id) {
            $params['organization_id'] = $organization_id;
        }
        if ($filter_organization_id) {
            $params['filter_organization_id'] = $filter_organization_id;
        }
        if ($search) {
            $params['search'] = "%{$search}%";
        }
        if ($status_id) {
            $params['status_id'] = $status_id;
        }
        if ($priority_id) {
            $params['priority_id'] = $priority_id;
        }
        if ($due_date) {
            $params['due_date'] = $due_date;
        }

        $results = DB::select($query, $params);
        $collection = collect($results);

        $page = (int) request()->get('page', 1);

        return new LengthAwarePaginator(
            $collection->forPage($page, $limit)->values(),
            $collection->count(),
            $limit,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function buildVisibilityCondition(bool $actor_is_global, ?string $organization_id, ?string $filter_organization_id = null): string
    {
        if ($actor_is_global) {
            if ($filter_organization_id) {
                return "T.user_id = :user_id OR (T.visibility = 'organization' AND T.organization_id = :filter_organization_id)";
            }

            return "T.user_id = :user_id OR T.visibility = 'organization'";
        }

        if ($organization_id) {
            return "T.user_id = :user_id OR (T.visibility = 'organization' AND T.organization_id = :organization_id)";
        }

        return 'T.user_id = :user_id';
    }
}
