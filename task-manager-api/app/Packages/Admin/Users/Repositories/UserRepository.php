<?php

namespace App\Packages\Admin\Users\Repositories;

use App\Base\Repository\BaseRepository;
use App\Packages\Admin\Users\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function listWithFilters(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 15);
        $offset = ($page - 1) * $limit;

        $sql = "
            WITH user_list AS (
                SELECT 
                    U.id,
                    U.name,
                    U.email,
                    U.avatar_path,
                    U.created_at,
                    R.name as role_name,
                    R.slug as role_slug,
                    S.name as status_name,
                    S.slug as status_slug
                FROM admin.users U
                JOIN admin.roles R ON U.role_id = R.id
                LEFT JOIN admin.user_statuses S ON U.last_status_id = S.id
                WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (U.name ILIKE :search OR U.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['role_id'])) {
            $sql .= " AND U.role_id = :role_id";
            $params['role_id'] = $filters['role_id'];
        }

        if (!empty($filters['status_id'])) {
            $sql .= " AND U.last_status_id = :status_id";
            $params['status_id'] = $filters['status_id'];
        }

        $sql .= "
            ),
            total_count AS (
                SELECT COUNT(*) as count FROM user_list
            )
            SELECT 
                user_list.*,
                total_count.count as total
            FROM user_list, total_count
            ORDER BY user_list.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $results = \Illuminate\Support\Facades\DB::select($sql, $params);
        $total = $results[0]->total ?? 0;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $total,
            $limit,
            $page,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => $filters]
        );
    }
}
