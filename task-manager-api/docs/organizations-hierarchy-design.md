# Organizations + Hierarquia — Plano (não implementado ainda)

## Objetivo

Transformar o sistema de mono-tenant (todo mundo vê os mesmos dados) para
multi-tenant real: cada `Organization` isola seus próprios usuários e
tarefas, organizadas em árvore (matriz → filiais → sub-unidades).

## Por que multi-tenant real (não só agrupamento)

Um agrupamento sem isolamento de dados já é coberto pelas roles atuais
(Owner/Admin/User). O ganho real de "organizations" só existe se os dados
forem de fato segregados — senão é só uma tag decorativa.

## Regra de acesso (como Owner/Admin/User interagem com a árvore)

| Role | Escopo de dados |
|------|------------------|
| **Owner** | Global — enxerga e administra **todas** as organizations, sem restrição. Não pertence a nenhuma organization específica (ou pertence à raiz da árvore, mas isso é cosmético). |
| **Admin** | Escopado à sua organization **+ todas as descendentes** na árvore (ex: admin da matriz vê as filiais). Não vê organizations irmãs/fora da própria ramificação. |
| **User** | Escopado só à própria organization (sem descendentes). |

Isso é uma segunda dimensão de hierarquia, ortogonal à de `level` que já
existe em `admin.roles` — `level` decide "quem pode mexer em quem"
(role), a árvore de organizations decide "quem vê os dados de quem"
(tenant).

## Modelo de dados

```
admin.organizations
    id uuid pk
    name string
    slug string unique
    parent_id uuid nullable, fk -> admin.organizations.id
    created_at, updated_at, deleted_at (soft delete)
```

- **Adjacency list** (`parent_id`), não materialized path — mais simples
  de manter (criar/mover uma organization é 1 update), e o projeto já
  tem o padrão de CTE recursiva em raw SQL (`TaskRepository`,
  `UserRepository`) pra resolver descendentes:

  ```sql
  WITH RECURSIVE descendants AS (
      SELECT id FROM admin.organizations WHERE id = :org_id
      UNION ALL
      SELECT o.id FROM admin.organizations o
      JOIN descendants d ON o.parent_id = d.id
  )
  SELECT id FROM descendants;
  ```

- `admin.users` ganha `organization_id` (FK, obrigatório para Admin/User;
  nulo ou "organization raiz" para Owner — a decidir na hora).
- `public.tasks` já tem `user_id` — escopo por organization vem via join
  em `users.organization_id`, não precisa de coluna nova na tabela de
  tarefas.

## Migração de dados existentes

Criar uma organization raiz (`root`, sem `parent_id`) na migration que
adiciona a coluna, e mover todos os usuários existentes pra ela — ninguém
fica sem organization no meio do caminho.

## Permissões novas

`admin.organizations.list`, `.create`, `.update`, `.delete`, `.manage-users`
(mover um usuário entre organizations) — seguindo o padrão allow-list já
usado (`AuthSqlMiddleware`/`AuthenticateMiddleware` deste projeto).

## Onde isso entra no fluxo de auth

`AuthenticateMiddleware`/`TokenInCacheService` precisa passar a carregar
também `organization_id` (+ a lista de descendentes, calculada uma vez e
cacheada) no payload do usuário em cache — os Repositories de
Task/User/etc. passam a filtrar por esse escopo, do mesmo jeito que hoje
já filtram por `deleted_at IS NULL`.

## Endpoints (rascunho)

```
GET    /admin/organizations            # árvore (ou lista flat com parent_id, front monta a árvore)
POST   /admin/organizations            # criar (nome, parent_id)
PUT    /admin/organizations/{id}        # renomear / mover (trocar parent_id)
DELETE /admin/organizations/{id}        # soft delete (bloqueia se tiver usuários/filhas ativas)
PATCH  /admin/users/{id}/organization   # mover usuário de organization
```

## UI (rascunho)

- Tela `/admin/organizations`: árvore expansível (reaproveita o padrão
  visual de `RoleTable`/`RoleFormModal`), com criar/renomear/mover.
- `UserFormModal`: campo de organization (só mostra organizations dentro
  do escopo do ator — mesma lógica de "roles atribuíveis" que acabamos
  de implementar pros roles).
- Owner vê um seletor pra "entrar" na visão de uma organization
  específica (já que ele não pertence a nenhuma) — nice-to-have, não
  bloqueia o MVP.

## Fases sugeridas (quando for implementar)

1. **Backend puro**: migration + Model + escopo nos Repositories de
   User/Task + permissões. Sem UI ainda — testável via Pest/curl.
2. **UI de gestão de organizations**: tela de árvore + criar/mover.
3. **UI de atribuição de usuário**: campo organization no formulário de
   usuário, filtrado por escopo.
4. **Polish do Owner**: seletor de "visão como organization X" (opcional).

## Perguntas em aberto (decidir antes de começar a fase 1)

- Owner pertence à organization raiz ou fica genuinamente fora da árvore
  (`organization_id` nulo)? Isso afeta se o Owner "aparece" nas listagens
  de usuários de alguma organization.
- Uma organization pode ficar sem nenhum Admin (só Users)? Se sim, quem
  administra ela — o Admin do pai na árvore, por herança?
- Exclusão de organization com usuários/filhas ativas: bloqueia (como já
  fazemos hoje pra `Role` com `DeleteRoleService`) ou pede
  realocação/cascata?
