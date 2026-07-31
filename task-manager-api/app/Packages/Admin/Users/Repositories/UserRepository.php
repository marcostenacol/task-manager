<?php

namespace App\Packages\Admin\Users\Repositories;

use App\Base\Repository\BaseRepository;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

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

    public function listWithFilters(array $filters, ?array $organizationIds = null): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 15);
        $offset = ($page - 1) * $limit;

        $sql = '
            WITH user_effective_role AS (
                SELECT
                    U.id as user_id,
                    COALESCE(
                        U.global_role_id,
                        (SELECT UO.role_id FROM admin.user_organizations UO
                         WHERE UO.user_id = U.id AND UO.organization_id = U.active_organization_id
                         LIMIT 1),
                        U.role_id
                    ) as effective_role_id
                FROM admin.users U
            ),
            user_list AS (
                SELECT
                    U.id,
                    U.name,
                    U.email,
                    U.avatar_path,
                    U.created_at,
                    U.active_organization_id,
                    R.id as role_id,
                    R.name as role_name,
                    R.slug as role_slug,
                    R.level as role_level,
                    R.color as role_color,
                    O.id as organization_id,
                    O.name as organization_name,
                    O.slug as organization_slug,
                    S.name as status_name,
                    S.slug as status_slug
                FROM admin.users U
                JOIN user_effective_role UER ON UER.user_id = U.id
                JOIN admin.roles R ON R.id = UER.effective_role_id
                LEFT JOIN admin.organizations O ON O.id = U.active_organization_id
                LEFT JOIN admin.user_statuses S ON U.last_status_id = S.id
                WHERE U.deleted_at IS NULL
                AND R.deleted_at IS NULL
        ';

        $params = [];

        if ($organizationIds !== null) {
            if (empty($organizationIds)) {
                $sql .= ' AND FALSE';
            } else {
                $placeholders = [];
                foreach (array_values($organizationIds) as $index => $organizationId) {
                    $placeholder = "org_scope_{$index}";
                    $placeholders[] = ":{$placeholder}";
                    $params[$placeholder] = $organizationId;
                }
                $sql .= ' AND U.active_organization_id IN ('.implode(',', $placeholders).')';
            }
        }

        if (! empty($filters['search'])) {
            $sql .= ' AND (U.name ILIKE :search OR U.email ILIKE :search)';
            $params['search'] = '%'.$filters['search'].'%';
        }

        if (! empty($filters['role_id'])) {
            $sql .= ' AND R.id = :role_id';
            $params['role_id'] = $filters['role_id'];
        }

        if (! empty($filters['status_id'])) {
            $sql .= ' AND U.last_status_id = :status_id';
            $params['status_id'] = $filters['status_id'];
        }

        if (! empty($filters['organization_id'])) {
            $sql .= ' AND U.active_organization_id = :filter_organization_id';
            $params['filter_organization_id'] = $filters['organization_id'];
        }

        $sql .= '
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
        ';

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $results = DB::select($sql, $params);
        $total = $results[0]->total ?? 0;

        return new LengthAwarePaginator(
            $results,
            $total,
            $limit,
            $page,
            ['path' => Request::url(), 'query' => $filters]
        );
    }
}
