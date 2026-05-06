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
     * Lista tarefas com filtros usando CTE para melhor performance
     */
    public function listWithFilters(string $userId, array $filters = []): LengthAwarePaginator
    {
        $limit = $filters['limit'] ?? 15;
        $search = $filters['search'] ?? null;
        $statusId = $filters['status_id'] ?? null;
        $priorityId = $filters['priority_id'] ?? null;
        $dueDate = $filters['due_date'] ?? null;

        $query = "
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
                WHERE T.user_id = :user_id
                " . ($search ? "AND (T.title ILIKE :search OR T.description ILIKE :search)" : "") . "
                " . ($statusId ? "AND T.status_id = :status_id" : "") . "
                " . ($priorityId ? "AND T.priority_id = :priority_id" : "") . "
                " . ($dueDate ? "AND T.due_date::date = :due_date::date" : "") . "
            )
            SELECT * FROM tasks_filtered
            ORDER BY priority_order DESC, due_date ASC NULLS LAST, created_at DESC
        ";

        $params = ['user_id' => $userId];
        if ($search) $params['search'] = "%{$search}%";
        if ($statusId) $params['status_id'] = $statusId;
        if ($priorityId) $params['priority_id'] = $priorityId;
        if ($dueDate) $params['due_date'] = $dueDate;

        $results = DB::select($query, $params);
        $collection = collect($results);

        $page = (int)request()->get('page', 1);
        
        return new LengthAwarePaginator(
            $collection->forPage($page, $limit)->values(),
            $collection->count(),
            $limit,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
