<?php

namespace App\Packages\Admin\Organizations\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
    ) {}

    public function execute(string $name, ?string $parent_id, string $actor_id, ?string $owner_cpf = null): Organization
    {
        return DB::transaction(function () use ($name, $parent_id, $actor_id, $owner_cpf) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(6),
                'parent_id' => $parent_id,
            ]);

            $this->assignOwner($organization, $owner_cpf);

            $this->record_audit_log_service->execute($actor_id, 'organization.create', 'Organization', $organization->id, [
                'name' => $organization->name,
                'parent_id' => $parent_id,
                'owner_cpf' => $owner_cpf,
            ], $organization->id);

            return $organization;
        });
    }

    private function assignOwner(Organization $organization, ?string $owner_cpf): void
    {
        if (! $owner_cpf) {
            return;
        }

        $owner = User::where('cpf', $owner_cpf)->first();

        throw_unless($owner, new \InvalidArgumentException('Nenhum usuário encontrado com esse CPF.'));

        $org_admin_role = Role::where('slug', 'org-admin')->firstOrFail();

        UserOrganization::create([
            'user_id' => $owner->id,
            'organization_id' => $organization->id,
            'role_id' => $org_admin_role->id,
        ]);

        $this->clearUserCache($owner->id);
    }
}
