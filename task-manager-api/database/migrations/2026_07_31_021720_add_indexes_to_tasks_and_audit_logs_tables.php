<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nem `public.tasks` nem `admin.audit_logs` tinham índice além da PK —
     * toda listagem filtrada (por dono, organization, status, visibilidade,
     * ator de auditoria) fazia sequential scan da tabela inteira. Invisível
     * com poucas linhas, mas é o próximo gargalo real assim que o volume
     * crescer (a paginação por LIMIT/OFFSET não evita o scan que acontece
     * antes do LIMIT ser aplicado).
     */
    public function up(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            $table->index('user_id', 'tasks_user_id_index');
            $table->index('organization_id', 'tasks_organization_id_index');
            $table->index('status_id', 'tasks_status_id_index');
            $table->index('visibility', 'tasks_visibility_index');
            $table->index('deleted_at', 'tasks_deleted_at_index');
        });

        Schema::table('admin.audit_logs', function (Blueprint $table) {
            $table->index('actor_id', 'audit_logs_actor_id_index');
            $table->index('organization_id', 'audit_logs_organization_id_index');
            $table->index('target_id', 'audit_logs_target_id_index');
            $table->index('created_at', 'audit_logs_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_id_index');
            $table->dropIndex('tasks_organization_id_index');
            $table->dropIndex('tasks_status_id_index');
            $table->dropIndex('tasks_visibility_index');
            $table->dropIndex('tasks_deleted_at_index');
        });

        Schema::table('admin.audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_actor_id_index');
            $table->dropIndex('audit_logs_organization_id_index');
            $table->dropIndex('audit_logs_target_id_index');
            $table->dropIndex('audit_logs_created_at_index');
        });
    }
};
