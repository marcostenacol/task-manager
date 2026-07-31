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

class CreateSubOrganizationService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $recordAuditLogService,
        private ResolveTargetOrganizationService $resolveTargetOrganizationService,
    ) {}

    /**
     * Antes só um ator global conseguia criar organization com `parent_id`
     * (tela de admin). Um Org Admin agora consegue criar uma sub-organization
     * da própria organization ativa por aqui — sempre da SUA organization,
     * nunca de uma arbitrária (`ResolveTargetOrganizationService` só permite
     * escolher livremente pra ator global; org-scoped sempre usa a própria
     * ativa). O ator org-scoped já entra como org-admin da sub-organization
     * criada, pra poder administrá-la de imediato.
     */
    public function execute(string $name, string $actorId, ?string $organizationId = null): Organization
    {
        return DB::transaction(function () use ($name, $actorId, $organizationId) {
            $actor = User::findOrFail($actorId);
            $parentOrganizationId = $this->resolveTargetOrganizationService->execute($actor, $organizationId);

            $subOrganization = Organization::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(6),
                'parent_id' => $parentOrganizationId,
            ]);

            if ($actor->global_role_id === null) {
                $orgAdminRole = Role::where('slug', 'org-admin')->firstOrFail();

                UserOrganization::create([
                    'user_id' => $actor->id,
                    'organization_id' => $subOrganization->id,
                    'role_id' => $orgAdminRole->id,
                ]);

                $this->clearUserCache($actorId);
            }

            $this->recordAuditLogService->execute($actorId, 'organization.create_sub', 'Organization', $subOrganization->id, [
                'name' => $subOrganization->name,
                'parent_id' => $parentOrganizationId,
            ], $subOrganization->id);

            $this->bumpCacheVersion('admin_users_list');

            return $subOrganization;
        });
    }
}
