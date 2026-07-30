<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationService
{
    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
    ) {}

    public function execute(string $name, ?string $parentId, string $actorId, ?string $ownerCpf = null): Organization
    {
        return DB::transaction(function () use ($name, $parentId, $actorId, $ownerCpf) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(6),
                'parent_id' => $parentId,
            ]);

            $this->assignOwner($organization, $ownerCpf);

            $this->recordAuditLogService->execute($actorId, 'organization.create', 'Organization', $organization->id, [
                'name' => $organization->name,
                'parent_id' => $parentId,
                'owner_cpf' => $ownerCpf,
            ], $organization->id);

            return $organization;
        });
    }

    private function assignOwner(Organization $organization, ?string $ownerCpf): void
    {
        if (! $ownerCpf) {
            return;
        }

        $owner = User::where('cpf', $ownerCpf)->first();

        throw_unless($owner, new \InvalidArgumentException('Nenhum usuário encontrado com esse CPF.'));

        $orgAdminRole = Role::where('slug', 'org-admin')->firstOrFail();

        UserOrganization::create([
            'user_id' => $owner->id,
            'organization_id' => $organization->id,
            'role_id' => $orgAdminRole->id,
        ]);
    }
}
