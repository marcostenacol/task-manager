# Auditoria de ações administrativas — spec reduzido

## O quê
Registrar quem fez o quê nas ações admin sensíveis: criar/editar/banir/ativar/trocar role de usuário. Sem isso, hoje nenhuma dessas ações deixa rastro de quem executou.

## Banco
Tabela `admin.audit_logs`:
- `id` uuid pk
- `actor_id` uuid (fk `admin.users`, quem fez)
- `action` string (`user.create`, `user.update`, `user.ban`, `user.activate`, `user.role_change`)
- `target_type` string (`User`)
- `target_id` uuid
- `metadata` jsonb (ex.: `{"reason": "..."}` no ban, `{"role_id": "..."}` na troca de role)
- `created_at` timestamp (sem `updated_at`, log é imutável)

## Backend
- `App\Packages\Admin\AuditLogs\Models\AuditLog`
- `App\Packages\Admin\AuditLogs\Services\RecordAuditLogService::execute(actorId, action, targetType, targetId, metadata = [])` — insert simples, chamado dentro da mesma `DB::transaction()` de `CreateUserService`, `UpdateUserService`, `BanUserService`, `ActivateUserService`, `ChangeUserRoleService`.
- `App\Packages\Admin\AuditLogs\Services\ListAuditLogsService` — paginado, mais recente primeiro, com nome do ator/alvo.
- `AuditLogController::index()` — `GET /v1/admin/audit-logs`, permissão nova `admin.audit-logs.list` (seguir padrão do `PermissionSeeder`, dada só pra role admin).

## Frontend
- `AdminService.listAuditLogs(filters)`
- Página `/admin/audit-logs` — mesmo padrão visual de `/admin/users` (tabela: ator, ação, alvo, quando).
- Link "Auditoria" na sidebar, condicional a `admin.audit-logs.list`.

## Fora de escopo
- CRUD de tarefas (só ações admin por enquanto).
- Trigger de banco / captura automática fora da API (registro é explícito no Service).
