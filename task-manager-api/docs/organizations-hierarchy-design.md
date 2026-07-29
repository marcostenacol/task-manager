# Organizations + Hierarquia — Plano (não implementado ainda)

## Objetivo

Transformar o sistema de mono-tenant (todo mundo vê os mesmos dados) para
multi-tenant real: cada `Organization` isola seus próprios usuários e
tarefas, organizadas em árvore (matriz → filiais → sub-unidades), com
usuário podendo pertencer a **mais de uma organization**, com uma role
possivelmente diferente em cada uma (ex: Admin na filial A, User na B).

## Por que multi-tenant real (não só agrupamento)

Um agrupamento sem isolamento de dados já é coberto pelas roles atuais
(Owner/Admin/User). O ganho real de "organizations" só existe se os dados
forem de fato segregados — senão é só uma tag decorativa.

## ⚠️ Decisão que muda o escopo do que já existe

`admin.users.role_id` (global, um valor por usuário) deixa de fazer
sentido — a role passa a ser **por membership** (usuário × organization),
não do usuário como um todo. Isso significa que quase toda a hierarquia
construída nesta sessão (auth payload, `AuthenticateMiddleware`,
`DeleteUserService`, `UpdateUserService`, `ChangeUserRoleService`,
`SyncRolePermissionsService`, `UpdateRoleLevelService`, os componentes de
frontend que leem `user.role`) precisa ser revisitada para operar **no
contexto de uma organization ativa da sessão**, não globalmente. Não é um
acréscimo aditivo — é um refactor do que já está em produção.

## Modelo de dados

```
admin.organizations
    id uuid pk
    name string
    slug string unique
    parent_id uuid nullable, fk -> admin.organizations.id
    created_at, updated_at, deleted_at (soft delete)

admin.user_organizations   -- pivot, substitui admin.users.role_id
    id uuid pk
    user_id uuid fk -> admin.users.id
    organization_id uuid fk -> admin.organizations.id
    role_id uuid fk -> admin.roles.id
    created_at, updated_at
    unique (user_id, organization_id)   -- 1 role por membership, não duplica
```

- `admin.users.role_id` é removido e substituído por
  `admin.users.global_role_id` (nullable, FK -> `admin.roles.id`, só
  aceita roles com `scope = global` — ver seção "Roles globais vs. roles
  de organization" abaixo) + as memberships em `user_organizations` pra
  tudo que é `scope = organization`.
- `public.tasks` continua sem coluna nova — mas o "dono" de uma tarefa
  passa a ser ambíguo sem saber **em qual organization** ela foi criada.
  Precisa de `organization_id` direto em `public.tasks` (não dá pra
  inferir só pelo `user_id`, já que o mesmo usuário pode ter tarefas em
  organizations diferentes).

## Árvore de organizations

**Adjacency list** (`parent_id`), não materialized path — mais simples
de manter (criar/mover é 1 update), e o projeto já tem o padrão de CTE
recursiva em raw SQL (`TaskRepository`, `UserRepository`) pra resolver
descendentes:

```sql
WITH RECURSIVE descendants AS (
    SELECT id FROM admin.organizations WHERE id = :org_id
    UNION ALL
    SELECT o.id FROM admin.organizations o
    JOIN descendants d ON o.parent_id = d.id
)
SELECT id FROM descendants;
```

## Roles globais vs. roles de organization

Owner não devia ser "só um caso especial hardcoded" — o mais limpo é
`admin.roles` ganhar uma coluna `scope` (`global` | `organization`):

```
admin.roles
    ...
    scope enum('global', 'organization') not null default 'organization'
```

| | Role **global** (ex: `Owner`) | Role **de organization** (ex: `Admin`, `User`, qualquer role custom) |
|---|---|---|
| Onde é atribuída | Direto no usuário: `admin.users.global_role_id` (nullable, FK -> `admin.roles.id`) | Só via membership: `admin.user_organizations.role_id` |
| Precisa de organization ativa? | Não — nem aparece no seletor de "trocar organization" | Sim, o acesso só existe dentro do contexto de uma membership |
| Isola dados por organization? | Não — bypassa o filtro de tenant inteiro | Sim, sempre filtrado pela organization ativa (+ descendentes, se Admin) |
| Quem pode criar uma role desse tipo | Só quem já tem uma role global (evita um Admin "inventar" uma role global pra si) | Qualquer ator dentro do próprio nível de hierarquia (regra que já existe hoje) |
| `level` compara com quem | Só com outras roles globais | Só com outras roles de organization **dentro da mesma organization/membership** |

Ou seja, **o que diferencia uma role global não é ela ser mais forte "no
papel"** — é ela viver fora do sistema de membership por completo. Um
usuário só tem uma role global (ou nenhuma) porque ela é uma coluna no
próprio usuário, não uma linha no pivot; já roles de organization ele
pode ter várias (uma por membership).

Isso também deixa a porta aberta pra outras roles globais no futuro (ex:
um "Auditor" global, somente-leitura, que enxerga todas as organizations
sem ser Owner) sem precisar inventar um novo mecanismo — só criar outra
role com `scope = global` e permissões mais restritas.

### Resolução de permissão no login/middleware

```
se admin.users.global_role_id existe:
    usuário opera com a role global (ignora organization ativa)
senão:
    usuário opera com a role da membership ativa em user_organizations
```

`AuthenticateMiddleware`/`TokenInCacheService` checam `global_role_id`
primeiro; só resolvem a membership ativa se ele for nulo.

## Regra de acesso (Owner/Admin/User + árvore)

| Role | Escopo de dados |
|------|------------------|
| **Owner** | Global — enxerga e administra **todas** as organizations, sem restrição. É uma role de `scope = global` atribuída em `admin.users.global_role_id` (ver seção acima) — não é uma membership. Não precisa "selecionar" organization pra ter acesso total, mas pode entrar na visão de uma organization específica pra operar como se fosse dela (nice-to-have). |
| **Admin** | Escopado à organization da membership ativa **+ descendentes** dela na árvore. Se o mesmo usuário é Admin em duas organizations não relacionadas, cada uma é um escopo isolado — trocar de organization ativa troca o escopo inteiro. |
| **User** | Escopado só à organization da membership ativa (sem descendentes). |

## Sessão / "organization ativa"

Como o usuário pode ter N memberships, o token/sessão precisa saber
**qual organization está ativa agora** — o mesmo princípio de trocar de
workspace no Slack/GitHub:

- No login, se o usuário tem só 1 membership, ela já entra ativa. Se tem
  mais de uma, front pergunta qual organization entrar (ou usa a última
  usada, guardada em `admin.users.last_active_organization_id` ou similar).
- Novo endpoint `POST /auth/switch-organization` — troca a organization
  ativa da sessão (reemite o token com a `role`/`permissions` daquela
  membership) sem precisar logar de novo.
- `AuthenticateMiddleware`/`TokenInCacheService` passam a cachear
  `{ user, active_organization_id, role, permissions }` — `role`/
  `permissions` deixam de ser fixos no usuário e passam a refletir a
  membership ativa.
- Owner não tem "organization ativa" obrigatória — ele opera fora do
  conceito, a menos que escolha entrar na visão de uma.

## Migração de dados existentes

1. Marcar a role `owner` existente com `scope = global`; `admin`/`user`/
   custom roles ficam com `scope = organization` (default).
2. Criar organization raiz (`root`, sem `parent_id`).
3. Para cada usuário com role `owner` hoje: setar `global_role_id` =
   id da role `owner`, sem criar membership nenhuma.
4. Para os demais usuários: criar 1 linha em `user_organizations`
   (`user_id`, `root.id`, `role_id` = o `role_id` atual do usuário).
5. Remover `admin.users.role_id` só depois de confirmar que todo o código
   novo lê a role via `global_role_id` ou via a membership ativa, nunca
   mais direto de uma coluna única no usuário.

## Permissões novas

`admin.organizations.list`, `.create`, `.update`, `.delete`,
`.manage-memberships` (adicionar/remover usuário de uma organization,
trocar a role dele numa organization específica) — allow-list, mesmo
padrão de `AuthenticateMiddleware`.

## Endpoints (rascunho)

```
GET    /admin/organizations                        # árvore
POST   /admin/organizations                         # criar (nome, parent_id)
PUT    /admin/organizations/{id}                     # renomear / mover
DELETE /admin/organizations/{id}                     # soft delete
GET    /admin/organizations/{id}/members             # memberships da organization
POST   /admin/organizations/{id}/members             # adicionar usuário (user_id, role_id)
PUT    /admin/organizations/{id}/members/{user_id}    # trocar role da membership
DELETE /admin/organizations/{id}/members/{user_id}    # remover usuário da organization
POST   /auth/switch-organization                    # trocar organization ativa da sessão
```

## UI (rascunho)

- Tela `/admin/organizations`: árvore expansível, criar/renomear/mover.
- Tela de membros de uma organization: adicionar/remover usuário,
  escolher a role **daquela** membership (não mais um campo único no
  `UserFormModal` de "a role do usuário").
- Seletor de organization ativa na sidebar (só aparece se o usuário tiver
  mais de 1 membership) — trocar recarrega os dados escopados.
- Owner: seletor pra "entrar" numa organization específica.

## Fases sugeridas (quando for implementar)

1. **Modelo + migração de dado**: `organizations`, `user_organizations`,
   `organization_id` em `tasks`, migração dos usuários existentes pra
   `root`. Sem trocar nenhum fluxo de auth ainda.
2. **Auth com organization ativa**: login retorna a lista de memberships;
   `switch-organization`; `AuthenticateMiddleware` passa a resolver
   role/permissions pela membership ativa, não pelo usuário.
3. **Escopo nos Repositories**: Task/User filtrados por
   organization ativa + descendentes (Admin) — testável via Pest/curl,
   sem UI ainda.
4. **UI de gestão de organizations e membros**.
5. **UI de troca de organization ativa** (sidebar) + visão do Owner.

## Perguntas em aberto (decidir antes de começar a fase 1)

- Uma organization pode ficar sem nenhum Admin (só Users)? Se sim, quem
  administra ela — o Admin da organization pai, por herança?
- Uma tarefa criada numa organization pode ser vista/movida se o usuário
  trocar de organization ativa depois? (provavelmente não — tarefa fica
  presa à organization em que foi criada, mesmo que o autor tenha saído
  dela).
- Exclusão de organization com memberships/filhas ativas: bloqueia (como
  já fazemos hoje pra `Role` com `DeleteRoleService`) ou pede
  realocação/cascata?
- Um usuário pode ter **duas memberships na mesma organization** (não faz
  sentido, `unique(user_id, organization_id)` já impede) — mas pode ter a
  **mesma role em organizations diferentes** sem problema (ex: Admin em A
  e Admin em B, memberships independentes).
