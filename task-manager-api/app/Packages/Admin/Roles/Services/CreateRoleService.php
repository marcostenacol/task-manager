<?php

namespace App\Packages\Admin\Roles\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRoleService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, string $actorId): Role
    {
        return DB::transaction(function () use ($name, $actorId) {
            $actorLevel = User::with('role')->findOrFail($actorId)->role->level;

            $role = Role::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'level' => $actorLevel + 1,
            ]);

            $this->recordAuditLogService->execute($actorId, 'role.create', 'Role', $role->id, [
                'name' => $role->name,
            ]);

            return $role;
        });
    }
}
