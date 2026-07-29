# Gestão de Roles e Permissões — spec reduzido

## O quê
Tela admin pra criar roles novas e marcar/desmarcar quais permissões cada role tem. Permissões em si continuam fixas (definidas no `PermissionSeeder`, cada uma corresponde a um `auth.api:xxx` real no código) — não dá pra criar permissão nova pela UI, só atribuir as que já existem a uma role.

## Backend
- `GET /v1/admin/roles/{id}` — detalhe de uma role com os `permission_id`s que ela tem hoje (join com `admin.role_has_permissions`).
- `GET /v1/admin/permissions` — lista todas as permissões (pro checklist).
- `POST /v1/admin/roles` — cria role nova (`name`, gera `slug` via `Str::slug($name)`).
- `PUT /v1/admin/roles/{id}/permissions` — sincroniza (`permission_ids: string[]`) — apaga o que não está na lista, insere o que não existe (delete+insert dentro de `DB::transaction()`, mesmo espírito do `UpdateContactsService` já existente no projeto).
- Nova permissão única `admin.roles.manage` (cobre as 4 rotas acima — é uma feature só-admin, não faz sentido granularizar em 4 permissões diferentes que só a própria role admin teria mesmo).
- Auditoria: `role.create` e `role.permissions_update` gravados via `RecordAuditLogService` (já existe).

## Frontend
- `/admin/roles` — lista de roles (nome, slug, quantidade de permissões), botão "Nova Role".
- Clicar numa role abre um modal (mesmo padrão do `UserFormModal`) com checkbox por permissão, agrupadas por prefixo (`admin.*`, `task.*`, `social.*`) pra não virar uma lista corrida de 20 itens.
- Link "Roles" na sidebar, condicional a `admin.roles.manage`.

## Fora de escopo
- Criar/editar permissão em si (permissão só existe se tiver um `auth.api:` correspondente no código — criar uma solta pela UI não faz nada).
- Deletar role (risco de deixar usuário com `role_id` órfão — fora de escopo, pedir depois se precisar).
