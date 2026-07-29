<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Support\Facades\DB;

class SyncRolePermissionsService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $roleId, array $permissionIds, string $actorId): Role
    {
        return DB::transaction(function () use ($roleId, $permissionIds, $actorId) {
            $role = Role::findOrFail($roleId);
            $role->permissions()->sync($permissionIds);

            $this->recordAuditLogService->execute($actorId, 'role.permissions_update', 'Role', $role->id, [
                'permission_ids' => $permissionIds,
            ]);

            return $role->load('permissions');
        });
    }
}
