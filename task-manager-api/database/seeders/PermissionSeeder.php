<?php

namespace Database\Seeders;

use App\Packages\Admin\Permissions\Models\Permission;
use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Admin Permissions
            ['name' => 'admin.users.list', 'description' => 'Listar usuários'],
            ['name' => 'admin.users.show', 'description' => 'Ver detalhes do usuário'],
            ['name' => 'admin.users.ban', 'description' => 'Banir usuário'],
            ['name' => 'admin.users.activate', 'description' => 'Reativar usuário'],
            ['name' => 'admin.users.role', 'description' => 'Alterar role do usuário'],
            ['name' => 'admin.users.status-history', 'description' => 'Ver histórico de status do usuário'],
            ['name' => 'admin.users.create', 'description' => 'Criar usuário'],
            ['name' => 'admin.users.update', 'description' => 'Editar usuário'],
            ['name' => 'admin.users.delete', 'description' => 'Excluir usuário'],
            ['name' => 'admin.audit-logs.list', 'description' => 'Ver logs de auditoria'],
            ['name' => 'admin.roles.manage', 'description' => 'Gerenciar roles e permissões'],

            // Task Permissions
            ['name' => 'task.tasks.create', 'description' => 'Criar tarefas'],
            ['name' => 'task.tasks.list', 'description' => 'Listar tarefas'],
            ['name' => 'task.tasks.show', 'description' => 'Ver detalhes da tarefa'],
            ['name' => 'task.tasks.update', 'description' => 'Editar tarefas'],
            ['name' => 'task.tasks.delete', 'description' => 'Deletar tarefas'],
            ['name' => 'task.tasks.status', 'description' => 'Alterar status da tarefa'],

            // Social Permissions
            ['name' => 'social.profile.show', 'description' => 'Ver perfil'],
            ['name' => 'social.profile.update', 'description' => 'Atualizar perfil'],
            ['name' => 'social.profile.avatar', 'description' => 'Upload de avatar'],
            ['name' => 'social.contacts.manage', 'description' => 'Gerenciar contatos'],
        ];

        $ownerRole = Role::withTrashed()->where('slug', 'owner')->first();
        $adminRole = Role::withTrashed()->where('slug', 'admin')->first();
        $userRole = Role::withTrashed()->where('slug', 'user')->first();

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['id' => (string) Str::uuid(), 'description' => $perm['description']]
            );

            // Owner gets everything
            if ($ownerRole) {
                DB::table('admin.role_has_permissions')->updateOrInsert([
                    'role_id' => $ownerRole->id,
                    'permission_id' => $permission->id,
                ]);
            }

            // Admin gets everything
            DB::table('admin.role_has_permissions')->updateOrInsert([
                'role_id' => $adminRole->id,
                'permission_id' => $permission->id,
            ]);

            // User gets specific permissions
            if (str_starts_with($perm['name'], 'task.') || str_starts_with($perm['name'], 'social.')) {
                DB::table('admin.role_has_permissions')->updateOrInsert([
                    'role_id' => $userRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
