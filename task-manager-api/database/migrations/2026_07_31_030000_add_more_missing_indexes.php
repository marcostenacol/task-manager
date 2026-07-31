<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Segunda leva de índices faltando, achada numa nova auditoria de
     * escalabilidade: `organization_id` de `user_organizations` só tinha um
     * índice único composto com `user_id` como primeira coluna (não serve
     * pra filtrar só por organization_id); `organizations.parent_id` é
     * usado toda hora no CTE recursivo de `ResolveOrganizationScopeService`
     * (praticamente todo request de ator não-global passa por ali);
     * `personal_access_tokens.user_id`/`user_has_statuses.user_id` não
     * tinham índice (só a FK, que o Postgres não indexa sozinho);
     * `roles.scope`/`organization_id` são filtrados em toda listagem de
     * roles.
     */
    public function up(): void
    {
        Schema::table('admin.user_organizations', function (Blueprint $table) {
            $table->index('organization_id', 'user_organizations_organization_id_index');
        });

        Schema::table('admin.organizations', function (Blueprint $table) {
            $table->index('parent_id', 'organizations_parent_id_index');
        });

        Schema::table('admin.personal_access_tokens', function (Blueprint $table) {
            $table->index('user_id', 'personal_access_tokens_user_id_index');
        });

        Schema::table('admin.user_has_statuses', function (Blueprint $table) {
            $table->index('user_id', 'user_has_statuses_user_id_index');
        });

        Schema::table('admin.roles', function (Blueprint $table) {
            $table->index('scope', 'roles_scope_index');
            $table->index('organization_id', 'roles_organization_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('admin.user_organizations', function (Blueprint $table) {
            $table->dropIndex('user_organizations_organization_id_index');
        });

        Schema::table('admin.organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_parent_id_index');
        });

        Schema::table('admin.personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('personal_access_tokens_user_id_index');
        });

        Schema::table('admin.user_has_statuses', function (Blueprint $table) {
            $table->dropIndex('user_has_statuses_user_id_index');
        });

        Schema::table('admin.roles', function (Blueprint $table) {
            $table->dropIndex('roles_scope_index');
            $table->dropIndex('roles_organization_id_index');
        });
    }
};
