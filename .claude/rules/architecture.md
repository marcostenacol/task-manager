# Arquitetura

## Backend (`task-manager-api`) — Laravel 13 / PHP 8.3 / PostgreSQL

### Fluxo real

```
Request → Controller → Service → (Repository, quando existe) → Model
                              ↕
                          Resource (response)
```

Confirmado lendo `app/Packages/Task/Tasks/` de ponta a ponta:

- `Controllers/TaskController.php` — injeta o Service certo por método (`ListTasksService`, `CreateTaskService`, `DetailTaskService`, `UpdateTaskService`, `DeleteTaskService`, `UpdateTaskStatusService`), delega, embrulha em `self::successResponse()`/`self::returnError()`.
- `Services/CreateTaskService.php` — `execute()` com `DB::transaction()`, chama `Task::create([...])` **direto no Model**, sem Repository.
- `Repositories/TaskRepository.php` — existe e é usado por `ListTasksService` (não lido neste harness, mas o método `listWithFilters()` usa uma CTE via `DB::select()` com bindings nomeados — ver abaixo).
- `Models/Task.php` — `HasUuids`, `SoftDeletes`, `$table = 'public.tasks'`, `$incrementing = false`, `$keyType = 'string'`.
- `Resources/TaskResource.php` — resposta.

**Repository é opcional, não obrigatório.** Quando a query é um `create()`/`update()`/`find()` simples, o Service chama o Model do Eloquent diretamente (visto em `CreateTaskService`). Quando a query é complexa (filtros dinâmicos, CTE, paginação manual), há um Repository dedicado (`TaskRepository::listWithFilters()`, `UserRepository`, `AuthRepository`). Ao criar uma Service nova, decida pelo mesmo critério — não crie um Repository "porque a regra diz para ter Repository" se a query é um `Model::create()`/`findOrFail()` trivial.

### Um Service por ação

Confirmado — `Task/Tasks/Services/` tem `CreateTaskService`, `UpdateTaskService`, `DeleteTaskService`, `DetailTaskService`, `ListTasksService`, `UpdateTaskStatusService`: seis classes, uma ação cada, todas com `execute()` como método público principal.

### Organização por domínio — 100% modular

Não existe legado flat (`app/Services/`, `app/Http/Controllers/{Domínio}`, `app/Models/{Domínio}`) neste projeto. Toda a árvore de domínio vive em:

```
app/Packages/{Domínio}/{Subdomínio}/
├── Controllers/
├── Services/
├── Repositories/        (quando aplicável)
├── Models/
├── Requests/
├── Resources/
├── Enum/                 (visto em Auth/Auth/Enum/SettingsEnum.php)
└── Middlewares/          (visto em Auth/Auth/Middlewares/)
```

Domínios reais confirmados: `Admin/Permissions`, `Admin/Roles`, `Admin/Settings`, `Admin/UserStatuses`, `Admin/Users`, `Auth/Auth`, `Social/Contacts`, `Social/Person`, `Task/Priorities`, `Task/Statuses`, `Task/Tasks`.

### Base real (`app/Base/`)

```
app/Base/
├── Http/Controllers/BaseController.php   # existe, mas NÃO é a base real dos Controllers de domínio
├── Repository/BaseRepository.php          # base real do Repository, quando o domínio tem um
└── Traits/
    ├── Response.php      # successResponse(), returnError(), failedValidationResponse(), notAuthorizeExceptionResponse()...
    ├── CacheTrait.php    # cache(), clearCache(), clearUserCache(), clearAccessToken(), clearRefreshToken()
    ├── AuditLog.php
    ├── HandlerLog.php    # usado internamente por Response e CacheTrait
    ├── HasLinks.php
    └── HasSlug.php
```

**Correção (2026-07-27): `BaseController` NÃO é scaffold morto — é usado de fato pelo domínio `Auth`.** `App\Base\Http\Controllers\BaseController` agrega `AuditLog, ValidatesRequests, AuthorizesRequests, Response` e estende `Illuminate\Routing\Controller`. Confirmado via `grep -rn "extends BaseController" app/`: `LoginController`, `RegisterController`, `LogoutController`, `RefreshTokenController` (`app/Packages/Auth/Auth/Controllers/`) estendem essa classe. Já `TaskController`, `AdminUserController`, `PersonController`, `ContactController` **não** a estendem — usam `App\Http\Controllers\Controller` + `use App\Base\Traits\Response;` direto. Dois padrões coexistem por domínio: `Auth` usa `BaseController`, os demais usam `Controller` + trait direta. Ao criar Controller novo fora do domínio `Auth`, siga o padrão majoritário (`extends Controller` + `use Response;`); se for adicionar a `Auth`, siga o padrão local (`extends BaseController`) por consistência com os irmãos do mesmo pacote.

`App\Base\Repository\BaseRepository` **é** usado de fato — `TaskRepository extends BaseRepository`, `$this->model = $model` (setado no construtor, não via `setModel()` do pai, embora o método exista). Métodos herdados reais: `all()`, `getPaginated()`, `findByColumn()`, `getByColumn()`, `update()`, `findOrFail()`, `deleteByColumn()`, `delete()`, `find()`, `save()`, `create()` (na prática `firstOrCreate()`, mesmo padrão de nomes invertidos visto em outros projetos AE3 — confira antes de assumir pelo nome).

### Middleware de autenticação

`App\Packages\Auth\Auth\Middlewares\AuthenticateMiddleware` (alias registrado em `bootstrap/app.php`: `'auth.api' => AuthenticateMiddleware::class`). Fluxo real:

- Token Bearer extraído via helper `handlerRequestToken($request->bearerToken())`.
- Validado contra `App\Packages\Auth\Auth\Services\Cache\TokenInCacheService`, que por sua vez consulta uma função **PL/pgSQL** no banco (ver `database/migrations/2026_04_09_090808_create_auth_flux.php` e `..._090809_refactor_auth_functions_and_fix_types.php` — não lidas em detalhe neste harness, mas o `README.md` confirma "Login, registro, logout e refresh token via PL/pgSQL").
- Expiração controlada por `SettingsEnum::TOKEN_EXPIRATION_MINUTES`, revogação via `UPDATE admin.personal_access_tokens SET expires_at = now()` + `Cache::forget`.
- Abilities passadas como parâmetros do middleware (`'auth.api:admin.users.list'`), verificadas via `array_intersect($abilities, $permissions)` contra `data_get(entityObject(), 'user.permissions', [])` — mesmo espírito allow-list de outros projetos AE3, mas fonte de permissões é `entityObject()`/cache, não uma tabela `spatie/laravel-permission`.

### Rotas

`routes/api.php` único (não há glob por domínio como em outros projetos AE3) — todas as rotas de `v1` estão no mesmo arquivo, agrupadas por `prefix`. **Correção (2026-07-27): o arquivo já usa `use` no topo para todos os Controllers**, sem FQCN inline (verificado via `grep -n "App\\\\" routes/api.php`, só retorna as linhas de `use`) — a violação documentada anteriormente não reflete mais o código real. Ao criar rota nova, mantenha o padrão real observado: importe o Controller via `use` no topo.

## Frontend (`task-manager-ui`) — Nuxt 4 / Vue 3

- `srcDir: 'app/'` (config em `nuxt.config.ts`) — todo o código de app fica em `task-manager-ui/app/`, não na raiz.
- Organização por domínio em `app/modules/{Domínio}/`: `auth`, `tasks`, `social`, `admin`, `Landing`, cada um com `services/`, `models/`, `hooks/`, `components/`.
- `app/pages/` segue o roteamento de arquivo padrão do Nuxt (`pages/tasks/`, `pages/admin/users/`).
- `app/composables/` e `app/middleware/` existem na raiz de `app/` (não dentro de módulo) — prováveis composables/middlewares transversais (ex.: guarda de rota autenticada). Confirme o conteúdo específico antes de assumir convenção — não foram lidos arquivo-a-arquivo neste harness.
- **Sem Pinia, sem axios, sem UI kit** no `package.json` (`nuxt`, `vue`, `vue-router` apenas) — qualquer padrão de estado global ou client HTTP documentado deve ser confirmado lendo `app/modules/{domínio}/services/` e `app/modules/{domínio}/hooks/` primeiro; não presuma `useFetch`/`$fetch`/Pinia store sem checar o arquivo real do módulo que você for tocar.
- `components: [{ path: '~/modules', pathPrefix: false }, '~/components']` em `nuxt.config.ts` — componentes dentro de `modules/{domínio}/components/` são auto-importados sem prefixo de módulo no nome.
- **Deploy**: `Dockerfile` multi-stage (`nuxt build` → `.output/`, runtime `node .output/server/index.mjs`) e `docker-compose.yml` próprios (adicionados — antes o projeto só rodava via `npm run dev`/`make dev` direto no host, sem nenhum build de produção). Porta real (`PORT=25565` no `.env`, não a `3000` padrão do Nuxt) mantida no container. `nuxt.config.ts` tem `vite.server.allowedHosts` com o domínio de produção — necessário só quando roda via `nuxt dev` (Vite dev server bloqueia `Host` header desconhecido); a build de produção (`.output/server`) não usa esse allowlist, então a entrada pode ficar desatualizada sem quebrar produção, mas vale manter sincronizada se o domínio mudar.
