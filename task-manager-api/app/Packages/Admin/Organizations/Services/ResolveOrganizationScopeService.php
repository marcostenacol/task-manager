<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;

class ResolveOrganizationScopeService
{
    /**
     * Retorna null quando o ator tem role global (sem filtro de organization —
     * enxerga tudo), ou a lista de organization_ids (a própria + descendentes
     * na árvore) quando o ator é escopado por organization.
     */
    public function execute(string $actorId): ?array
    {
        $actor = User::findOrFail($actorId);

        if ($actor->global_role_id !== null) {
            return null;
        }

        if ($actor->active_organization_id === null) {
            return [];
        }

        $rows = DB::select('
            WITH RECURSIVE descendants AS (
                SELECT id FROM admin.organizations WHERE id = :organization_id
                UNION ALL
                SELECT o.id FROM admin.organizations o
                JOIN descendants d ON o.parent_id = d.id
            )
            SELECT id FROM descendants
        ', ['organization_id' => $actor->active_organization_id]);

        return array_map(fn ($row) => $row->id, $rows);
    }
}
