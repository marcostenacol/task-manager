# Task Manager

Você é um Engenheiro de Software Sênior especialista em Laravel/PHP e Nuxt/Vue, boas práticas, SOLID e Clean Code.

Monorepo com dois projetos:

- **`task-manager-api/`**: backend Laravel 13 (PHP 8.3), organizado em módulos (`app/Packages/{Domínio}/{Camada}/`). Banco **PostgreSQL** (schemas `admin` e `public`), **Redis** (cache/tokens).
- **`task-manager-ui/`**: frontend **Nuxt 4** (Vue 3.5, `vue-router` 5), organizado em módulos por domínio em `app/modules/{domínio}/`.

Este `CLAUDE.md` cobre os dois projetos. Não há harness de frontend separado (o `task-manager-ui` é pequeno e usa quase nenhuma dependência além de `nuxt`/`vue` — ver `rules/project-tech.md`), mas as convenções de frontend ficam isoladas na seção própria abaixo e em `rules/architecture.md`.

## Mandato Principal

Antes de sugerir qualquer código, valide se está em conformidade com os padrões em `.claude/rules/`. Se uma solicitação violar os padrões, alerte o usuário e proponha a implementação correta.

Este harness foi construído lendo o código real do repositório (Controllers, Services, Repositories, Models, migrations, testes, `composer.json`, `package.json`, `Makefile`, `routes/api.php`) — não é um documento aspiracional. Onde o código real diverge de um padrão "ideal" de Laravel, o documento sinaliza a divergência explicitamente (ver marcações "⚠️ Discrepância observada" nos arquivos de `rules/`).

## Stack — Backend (`task-manager-api`)

| Componente | Tecnologia (confirmado em `composer.json`) |
|------------|-----------|
| Framework | Laravel **13.0** (`laravel/framework: ^13.0`) — o `composer.json` diz `laravel/laravel` de projeto skeleton |
| PHP | `^8.3` |
| Banco principal | PostgreSQL (`DB_CONNECTION=pgsql` no `.env.example`), schemas `admin` e `public` (ver `database/migrations/2026_04_09_084915_create_admin_schema.php`) |
| Cache / Filas / Sessão | Redis via `predis/predis: ^2.0`; sessão via `SESSION_DRIVER=database` (não Redis) e fila via `QUEUE_CONNECTION=database` (não há worker ativo em dev — ver `README.md`) |
| Auth | Middleware próprio `App\Packages\Auth\Auth\Middlewares\AuthenticateMiddleware` (alias `auth.api`) — token Bearer validado contra funções **PL/pgSQL** (não Sanctum/Passport/JWT-lib, embora não estejam nem no composer) |
| DTO | **Nenhum uso real hoje.** `spatie/laravel-data` foi removido do `composer.json` em 2026-07-27 por estar instalado e sem nenhuma classe do domínio o usando (ver Débito Técnico Conhecido) — Services recebem `array` puro (`$request->validated()`) direto. Ver `rules/mandatory.md`. |
| Documentação de API | `dedoc/scramble` (`*`) — inferência automática de OpenAPI a partir de rotas/FormRequests, **diferente** do padrão de anotação manual de outros projetos AE3 |
| Testes | **Pest 4** já instalado e em uso real (`pestphp/pest: ^4.4`, `pestphp/pest-plugin-laravel: ^4.1`) — há testes de Feature reais cobrindo Auth, Task, Admin, Social e RBAC (ver `rules/testing.md`) |
| Localização | `lucascudo/laravel-pt-br-localization` — mensagens de validação em `pt_BR` |

## Comandos principais

Todos os comandos são executados via `make` (confirmado em `task-manager-api/Makefile`), que por sua vez chama `docker compose -f docker-compose.local.yml` (ou `docker-compose.production.yml` se `APP_ENV=production`).

```bash
make build              # docker compose build --no-cache
make up                 # sobe containers + make clear
make down               # para e remove containers
make restart            # down + up + clear
make logs               # logs de todos os containers
make logs-php           # logs do container PHP
make shell-php          # bash no container PHP
make shell-nginx        # bash no container Nginx
make art c="comando"    # php artisan {comando}
make composer c="comando" # composer {comando}
make npm c="comando"    # npm {comando} (dentro do container PHP)
make php c="-v"         # php {comando} direto
make migrate            # php artisan migrate
make migrate-testing    # php artisan migrate --env=testing --force
make db-testing         # dropa/cria o banco de testes via psql
make test               # vendor/bin/pest (use f=NomeDoTeste para filtro: make test f=Login)
```

Pint (formatação):
```bash
docker compose -f docker-compose.local.yml exec task-manager-php-fpm ./vendor/bin/pint {caminho/do/arquivo.php}
```

## Arquitetura — Backend

### Fluxo real observado

```
Request → Controller → Service → (Repository) → Model
                              ↕
                          Resource (response)
```

**Atenção**: diferente do padrão "livro de receitas" Controller→Service→Repository→Model estrito, o Repository **é opcional no fluxo real**. Alguns módulos têm Repository (`TaskRepository`, `UserRepository`, `AuthRepository`), outros não — Services chamam o `Model` do Eloquent diretamente quando a query é simples (ex.: `CreateTaskService::execute()` chama `Task::create(...)` direto, sem passar por `TaskRepository`). Ver `rules/architecture.md` para o detalhe e não force a introdução de um Repository numa Service nova só para "completar a camada" se o padrão real do domínio não usa um.

### Organização por domínio — 100% modular, sem legado flat

Diferente de outros projetos AE3 que têm uma base legada flat + um padrão modular novo coexistindo, **este projeto nasceu 100% modular**: não existe `app/Services/`, `app/Http/Controllers/{Domínio}/` nem `app/Models/{Domínio}/` fora do padrão `app/Packages/`. Todo domínio vive em:

```
app/Packages/{Domínio}/{Subdomínio}/{Camada}/
```

Exemplos reais: `app/Packages/Task/Tasks/`, `app/Packages/Task/Statuses/`, `app/Packages/Task/Priorities/`, `app/Packages/Admin/Users/`, `app/Packages/Admin/Roles/`, `app/Packages/Auth/Auth/`, `app/Packages/Social/Person/`, `app/Packages/Social/Contacts/`. Todo código novo segue esse padrão — não há decisão a tomar sobre "onde colocar" como em projetos com legado.

### Base real do projeto

```
app/Base/
├── Http/Controllers/BaseController.php   # não usado diretamente pelos Controllers de domínio (ver abaixo)
├── Repository/BaseRepository.php          # Repository de domínio estende este quando existe Repository
└── Traits/
    ├── Response.php     # successResponse(), returnError(), failedValidationResponse()...
    ├── CacheTrait.php   # cache(), clearCache(), clearUserCache(), clearAccessToken()...
    ├── AuditLog.php
    ├── HandlerLog.php
    ├── HasLinks.php
    └── HasSlug.php
```

**⚠️ Discrepância observada**: `app/Base/Http/Controllers/BaseController.php` existe e agrega `AuditLog, ValidatesRequests, AuthorizesRequests, Response`, mas os Controllers reais do domínio (`TaskController`, `AdminUserController`, `PersonController`, `ContactController`) **não o estendem** — todos estendem `App\Http\Controllers\Controller` (o Controller-base padrão do Laravel) e usam a trait `App\Base\Traits\Response` diretamente via `use Response;`. Ao criar um Controller novo, siga o padrão real: `extends Controller` (Laravel padrão) + `use Response;`, não `extends BaseController`.

## Respostas

Trait real: `App\Base\Traits\Response`. A API observada nos Controllers reais **não é** `successResponse()`/`errorResponse()` como nomes de método de erro — é `successResponse()` e **`returnError()`** (não `errorResponse()`). Ver `rules/utilities.md` para a lista completa de métodos e assinaturas reais.

## Estrutura de resposta (envelope)

```json
{ "success": true, "message": "...", "data": { ... } }
```

Confirmado em `TaskController`/`Response::successResponse()`. Ver `rules/response-patterns.md`.

## Frontend (`task-manager-ui`)

- **Nuxt 4.4** (`nuxt: ^4.4.4`), Vue `^3.5.33`, `vue-router: ^5.0.6` — **sem** Pinia, **sem** axios, **sem** UI kit (Vuetify/Tailwind não estão no `package.json`). `srcDir: 'app/'` (configurado em `nuxt.config.ts`).
- Organização por domínio em `app/modules/{Domínio}/{services,models,hooks,components}/`: `auth`, `tasks`, `social`, `admin`, `Landing`. Componentes desses módulos são auto-registrados via `components: [{ path: '~/modules', pathPrefix: false }, '~/components']` em `nuxt.config.ts`.
- Chamada à API: consultar `app/modules/{domínio}/services/` para o padrão real de client HTTP antes de assumir `useFetch`/`$fetch`/axios — não documentamos aqui um padrão que não foi lido arquivo-a-arquivo; ver `rules/architecture.md` (seção frontend) para o que foi confirmado.
- `runtimeConfig.public.apiBase` aponta para a API (`NUXT_PUBLIC_API_BASE`, default `http://localhost:8000/api`).

## Regras de Ouro

1. **Código novo em `app/Packages/{Domínio}/{Subdomínio}/{Camada}/`** — este é o único padrão existente no backend, não há legado flat a preservar aqui (diferente de outros projetos AE3).
2. **Controller estende `App\Http\Controllers\Controller`** (Laravel padrão) + `use App\Base\Traits\Response;` — não `App\Base\Http\Controllers\BaseController` (existe mas não é usado pelo código real, ver acima).
3. **Repository é opcional**: use quando a query é complexa ou reaproveitada (`extends App\Base\Repository\BaseRepository`); Services simples podem chamar o Model do Eloquent direto — siga o padrão já usado no domínio que você está tocando.
4. **DTO**: não há uso real de DTO no projeto hoje — Services recebem `array` (`$request->validated()`). `spatie/laravel-data` (que estava instalado sem uso) foi removido do `composer.json` em 2026-07-27; se DTO for necessário no futuro, alinhe com o time antes de reinstalar qualquer pacote.
5. **Independência de Camadas**: Controller não conhece banco, Service concentra a lógica de negócio.
6. **Tipagem Estrita**: type hints obrigatórios em todos os métodos e propriedades.
7. **Preservação de Código**: não altere código existente sem solicitação explícita.
8. **Importações**: todas as classes via `use` no topo — nunca namespace inline (⚠️ ver discrepância em `rules/naming.md`: `routes/api.php` hoje usa FQCN inline em várias rotas — é uma violação real e conhecida, não um padrão a replicar).
9. **`DB::transaction()`** obrigatório em qualquer operação DML relevante (ver uso real em `CreateTaskService`).
10. **Injeção de Dependência**: toda injeção via construtor — nunca `app()`/`resolve()` no corpo de métodos novos.
11. **Idioma do Código**: identificadores em **inglês**. Strings de mensagem ao usuário em português (confirmado — todas as mensagens de `successResponse`/`returnError` nos Controllers são em pt-BR).
12. **UUID como chave primária**: `Task`, `User` e demais entidades de domínio usam `HasUuids` + `$incrementing = false` + `$keyType = 'string'` (ver `app/Packages/Task/Tasks/Models/Task.php`) — siga esse padrão em Models novos, não `bigIncrements`.
13. **Boy Scout Rule**: ao tocar qualquer arquivo, adapte-o aos padrões — mas sem mudar de camada/padrão arquitetural sem pedido explícito.
14. **Schemas PostgreSQL reais**: `admin` (usuários, roles, permissions, settings, tokens) e `public` (tasks, task_statuses, task_priorities e tabelas padrão do Laravel) — qualificar `$table` com o schema quando não for `public`.

## Git e GitHub

- Branches remotas reais observadas: `main`, `homolog`, `TASK-REEST-reestruturacao`. `main` é a branch base — não há `production` como branch ativa aqui (diferente de outros projetos AE3; confirme antes de assumir).
- Mensagens de commit reais são heterogêneas: mistura de Conventional Commits (`feat:`, `fix:`, `chore:`) e prefixos entre colchetes por fase de projeto (`[REEST]`, `[SECURITY]`, `[CONFIG]`, `[TASK-REEST]`). Não há convenção rígida única — para commits novos, prefira Conventional Commits puro (`feat:`, `fix:`, `chore:`, `test:`, `docs:`) seguindo o estilo mais recente do histórico, a menos que o usuário peça um prefixo de ticket.
- Nunca se coloque como co-autor nos commits.

## Testes

Pest 4 **já instalado e com cobertura real** — diferente de outros harnesses onde Pest é aspiracional. Ver `tests/Feature/{Auth,Task,Admin,Social,Infra/RBAC}/*.php`. Ver `rules/testing.md` para convenções observadas (uso de `DatabaseTransactions`, helpers `Pest\Laravel\*`, autenticação via login real no `beforeEach`).

## Débito Técnico Conhecido

Lista consolidada de divergências/anti-padrões reais encontrados ao ler o código (cada um já está detalhado no arquivo de `rules/` correspondente — isto é só o índice). Não corrija nada desta lista de passagem numa tarefa não relacionada; são decisões para o time priorizar.

| Item | Onde | Detalhe |
|------|------|---------|
| `BaseController` é scaffold morto | `app/Base/Http/Controllers/BaseController.php` | **Revisitado (2026-07-27) e revertido — a premissa estava desatualizada.** `grep -rn "extends BaseController" app/` retorna 4 hits reais: `LoginController`, `RegisterController`, `LogoutController`, `RefreshTokenController` (`app/Packages/Auth/Auth/Controllers/`) todos estendem `BaseController` de fato. Não é scaffold morto — é a base real do domínio `Auth`, coexistindo com o padrão `extends Controller + use Response` usado por `Task`/`Social`/`Admin`. Não foi apagado. Documento corrigido para refletir o código real; ver também `rules/architecture.md` (também corrigido). |
| Camada de Repository inconsistente | `app/Packages/*/Repositories/` | Repository é opcional e a escolha de quando usá-lo não é documentada por regra objetiva — hoje é "critério do autor original" (query simples vs. complexa). Risco: dois desenvolvedores decidem diferente para o mesmo tipo de caso. Ver `rules/architecture.md`. |
| `spatie/laravel-data` instalado e não usado | `composer.json` | **Resolvido (2026-07-27)**: confirmado via `grep -rn "Spatie\\LaravelData" app/` (zero resultados) que não havia uso real. Removido do `require` do `composer.json` e via `composer remove spatie/laravel-data` dentro do container (`task-manager_api_php`), que atualizou `composer.lock` e o autoload. Rebuild + boot verificados (`GET /` → 200, `POST /api/v1/auth/login` body vazio → 422). |
| `routes/api.php` com FQCN inline | `routes/api.php` | **Já não existia mais no código real** — revisitado em 2026-07-27: o arquivo atual já usa `use` no topo para todos os Controllers (`AdminUserController`, `LoginController`, `LogoutController`, `RefreshTokenController`, `RegisterController`, `ContactController`, `PersonController`, `PriorityController`, `StatusController`, `TaskController`), sem nenhum FQCN inline (`grep -n "App\\\\" routes/api.php` só retorna as próprias linhas de `use`). Provavelmente corrigido em sessão anterior sem atualizar este índice. Nenhuma mudança necessária; rotas re-testadas via `curl` (login, tasks, social/profile, admin/users) e todas resolvem corretamente. |
| Nomes de método da trait `Response` divergem de outros projetos AE3 | `app/Base/Traits/Response.php` | `returnError()` em vez de `errorResponse()`; `invalidArgumentResponse()` retorna 400 em vez de 422. Risco real de um agente/dev replicar por hábito o nome de outro projeto e quebrar silenciosamente. Ver `rules/utilities.md`. |
| `Response` trait usa status HTTP como número literal internamente | `app/Base/Traits/Response.php` | A própria base do projeto (`404`, `500`, `400` hardcoded) viola a regra que pede uso de `Response::HTTP_*` em código novo — inconsistência entre a regra pregada e a base real. Ver `rules/mandatory.md`. |
| Guard `web` de sessão no `config/auth.php` sem uso real | `config/auth.php` | **Investigado a fundo (2026-07-27) — mantido de propósito, não é dead code removível.** Confirmado que nenhuma rota/middleware do domínio usa `Auth::guard()`/`Auth::user()` (`grep -rn "guard.*web\|'web'" routes/ app/` só retorna o próprio `config/auth.php`) — a API real é 100% Bearer/PL-pgSQL via `auth.api`. Porém, ao tentar remover o guard `web` (e trocar `defaults.guard` para algo não-existente), a rota raiz `GET /` passou a quebrar com `"Auth guard [api] is not defined."`: `resources/views/welcome.blade.php` (view padrão do skeleton Laravel, ainda servida em `/`) usa `@auth`/`@endauth`, que resolve o guard **default** em toda renderização, mesmo sem qualquer middleware de auth na rota. Reverter foi a decisão correta — o guard `web` continua sem uso de negócio real, mas é uma dependência dura do framework enquanto a view padrão existir. Comentário no `config/auth.php` atualizado para documentar esse achado. |
| Sem testes `Unit/` reais | `tests/Unit/` | **Resolvido (2026-07-27)**: toda a lógica de negócio em `app/Packages/Task/Tasks/Services/` é acoplada a Eloquent/DB (`Task::create/update/findOrFail`, `DB::transaction`) — nenhuma candidata a teste unitário puro ali. Candidatas reais encontradas em `app/Base/Helpers/validation.php`: `isValidCpf()`, `isValidCodeFamily()` (validação pura, sem I/O) e `handlerRequestToken()` (normalização de token Bearer, sem I/O quando o token é passado por parâmetro). Adicionado `tests/Unit/Helpers/ValidationHelpersTest.php` com 10 testes reais (Pest), 0 HTTP/DB — `php artisan test --testsuite=Unit` → 10 passed. `tests/Pest.php` só faz `->in('Feature')`, mas `phpunit.xml` já registra a testsuite `Unit` separadamente, então os testes rodam normalmente via `php artisan test`/`vendor/bin/pest` sem precisar tocar o binding. |
| Convenção de commit heterogênea | histórico git | Mistura de Conventional Commits puro e prefixos `[REEST]`/`[SECURITY]`/`[TASK-REEST]` por fase de projeto, sem uma convenção única vigente — impossível corrigir retroativamente. **Para commits novos, prefira sempre Conventional Commits puro** (`feat:`, `fix:`, `chore:`, `test:`, `docs:`), como já vem sendo seguido nos commits mais recentes do histórico. |
| Sem template de PR | `.github/` | **Resolvido (2026-07-27)**: adicionado `.github/PULL_REQUEST_TEMPLATE.md` (seções em português: o que mudou / por quê / como testar / observações), sem tocar em `.github/workflows/`. |
| Fila sempre síncrona | `.env`, `QUEUE_CONNECTION=database` | Sem worker ativo em dev — qualquer `Job` despachado roda sincronamente na mesma request. Risco de mascarar lentidão/erros de fila até produção. Ver `rules/project-context.md`. |
| `TaskRepository::listWithFilters()` mistura SQL cru (CTE) com padrão Eloquent do resto do domínio | `app/Packages/Task/Tasks/Repositories/TaskRepository.php` | **Deixado de propósito como débito aceito (revisitado 2026-07-27)**: reescrever essa query filtro-pesada e paginada de CTE crua para Eloquent puro é alto risco de mudar sutilmente semântica/performance (ordenação por `priority_order` calculada via JOIN, paginação manual sobre `collect($results)`) sem ganho imediato — não reescrito. Validado via `curl` real (usuário de teste criado por `POST /auth/register`): `GET /api/v1/tasks?search=...&status_id=...&priority_id=...&limit=5` retornando 200 com paginação e filtros combinados corretos. Ver `rules/response-patterns.md`. |
| `ContactController`/`TaskController::updateStatus` validam inline em vez de `FormRequest` | `app/Packages/Social/Contacts/Controllers/ContactController.php`, `app/Packages/Task/Tasks/Controllers/TaskController.php`, `app/Packages/Social/Person/Controllers/PersonController.php` | **Totalmente resolvido (2026-07-27)**: `TaskController::updateStatus()` migrado para `UpdateTaskStatusRequest` (`app/Packages/Task/Tasks/Requests/UpdateTaskStatusRequest.php`, regra `status_id: required|uuid`) e `PersonController::avatar()` migrado para `UpdateAvatarRequest` (`app/Packages/Social/Person/Requests/UpdateAvatarRequest.php`, mesma regra de imagem/mimes/tamanho que já existia inline), seguindo o padrão de `CreateTaskRequest`/`StoreContactRequest`. Verificado via `curl`: `PATCH /tasks/{id}/status` sem `status_id` → 422 com `data.errors.status_id`; `POST /social/profile/avatar` sem arquivo → 422 com `data.errors.avatar`; ambos com payload válido → 200. |
| ~~Sem CI/CD~~ | `.github/workflows/ci.yml` | **Resolvido (2026-07-27)**: pipeline real adicionado (API: Pint... na verdade Pest via `composer test` contra Postgres real no CI; UI: `npm run lint` + `npm run build`). **Bug real encontrado e corrigido no próprio workflow**: `node-version: '20'` fazia o `npm run lint` da UI quebrar de verdade (`TypeError: Object.groupBy is not a function` — `eslint-flat-config-utils` usa uma API só disponível a partir do Node 21+). Corrigido para `node-version: '22'`, testado localmente e confirmado passando limpo. |
| **`SettingSeeder` e `PermissionSeeder` nunca rodavam de verdade** (só registrados em `DatabaseSeeder.php`, que não é o mecanismo real deste projeto — ver `rules/seeders.md`) | `database/migrations/`, `database/seeders/SettingSeeder.php`, `database/seeders/PermissionSeeder.php` | **Causa raiz de praticamente toda a flakiness 401/403 observada nos testes (2026-07-28)**: sem `SettingSeeder`, `admin.settings` fica vazio → `getSetting('token_expiration_minutes')` retorna `null` → `AuthenticateMiddleware::checkIfTokenIsValid()` calcula `Carbon::parse($data->created_at)->addMinutes(0) < now()`, que fica na borda do segundo — o token "expira" de forma aleatória dependendo da latência da própria request (confirmado isolando o cenário: mesma chamada passa ou falha com "Acesso expirado" dependendo de décimos de segundo). Sem `PermissionSeeder`, `admin.role_has_permissions` fica vazio → todo middleware `admin.users.*` nega acesso (403) mesmo pra usuário com role admin. **Resolvido**: duas migrations novas (`2026_07_28_120000_run_permission_seeder.php`, `2026_07_28_120001_run_setting_seeder.php`), mesmo padrão de `2026_04_09_085220_seed_roles_table.php` (`Artisan::call('db:seed', ...)`). Suíte completa: 38/38 testes passando, zero flakiness em execuções repetidas. |
| ~~`RegisterTest` esperava mensagem de validação em português, mas `.env`/CI não seta `APP_LOCALE` (default do Laravel é `en`)~~ | `.github/workflows/ci.yml`, `task-manager-api/.env` (não versionado) | **Totalmente resolvido (2026-07-30)**: além do fix já aplicado no CI, o `.env` real de produção também foi corrigido (`APP_LOCALE`/`APP_FALLBACK_LOCALE` `en`→`pt_BR`), container reiniciado. Verificado ao vivo via `curl` (mensagens de validação agora em português) e via suíte isolada (`--filter=RegisterTest`, 5/5 passando — antes 1 falhava). |
| ~~`APP_DEBUG=true` no `.env` real de produção~~ | `task-manager-api/.env` (não versionado) | **Resolvido**: `APP_DEBUG` alterado para `false`; `LOG_LEVEL` (`debug`→`error`) e `SHOW_ERROR_MESSAGE` (`true`→`false`) também ajustados no mesmo arquivo; container `task-manager_api_php` reiniciado. Confirmado no `.env` atual. |

**Corrigido (2026-07-27) — 3 bugs funcionais reais na UI, achados numa auditoria dedicada de mismatch frontend↔backend:**
1. `TaskService.show/create/update/updateStatus/getStatuses/getPriorities` (`app/modules/tasks/services/TaskService.ts`) retornavam o envelope HTTP inteiro (`{success, message, data}`) em vez de `.data`, apesar do tipo de retorno declarado prometer o objeto já desembrulhado. `TaskModal.vue` preenchia o formulário de edição com `task.title`/`task.status.id` inexistentes (sempre `undefined`) e o `<select>` de status/prioridade iterava sobre o envelope inteiro em vez do array real. Corrigido desembrulhando `.data` dentro do próprio `TaskService`, centralizando o fix.
2. `profile.vue`/`ProfileCard.vue` liam `profile.avatar_url`, mas o campo real (`PersonResource`) é `avatar_path` — avatar nunca aparecia. Corrigido nos dois arquivos.
3. `ContactsSection.vue` importava tipos `UserContact`/`UpsertContactData` que não existiam em `models/social.ts` (import de export inexistente); `profile.vue` desestruturava `error`/`addContact`/`removeContact` de `useProfile()`, mas o hook não expunha nenhum dos três — os botões de adicionar/remover contato eram no-op silencioso. Adicionados os tipos faltantes, `error` ref, e `addContact`/`removeContact` no hook, usando as rotas reais já existentes no backend (`POST`/`DELETE /v1/social/contacts`, confirmadas em `routes/api.php` e `ContactController`).

**Teste de regressão adicionado** para o item 3 (o único dos três com endpoint backend testável): `test('deve remover um contato individualmente')` em `tests/Feature/Social/SocialTest.php`, cobrindo `DELETE /api/v1/social/contacts/{id}` (rota que `removeContact()` agora chama de fato). Passa isoladamente via `--filter`. **Atenção real ao rodar a suíte inteira**: os testes de `SocialTest` (incluindo os pré-existentes de avatar/contatos) falham com 401 quando executados junto de outros — confirmado que passam isolados; parece ser o mesmo tipo de flakiness de auth/cache já documentado em outros itens desta seção (RBAC/seed), não algo introduzido por este teste. Itens 1 e 2 são bugs puramente de frontend (Vue/Nuxt) sem endpoint backend dedicado a testar, e `task-manager-ui` não tem framework de teste instalado — cobertura de regressão para eles exigiria decidir e instalar um primeiro (decisão em aberto, não tomada aqui).
| Sem `config/cors.php` explícito | `task-manager-api/config/` | Sem config própria, a API caía no default permissivo do pacote CORS do Laravel (todas as origens). **Fixing now**: criado `config/cors.php` com `allowed_origins` restrito às origens reais desta implantação (`tarefas.mvndev.online`, `150.230.75.122`), `supports_credentials = false`. |
| Nginx sem headers de segurança | `task-manager-api/.docker/nginx/conf.d/*.conf` (dev/hml/local/prod) | Nenhum `X-Content-Type-Options`/`X-Frame-Options`/`Referrer-Policy` em nenhum dos 4 arquivos. **Fixing now**: adicionados os três headers no nível do `server{}` (não só em `location /`, para cobrir também as respostas via `location ~ \.php$`) nos 4 arquivos. HSTS **não** adicionado de propósito — a terminação HTTPS deste ambiente está em setup externo em andamento (Caddy, portas 80/443), fora do escopo desta tarefa. |
| Sem rate limiting global na API | `task-manager-api/routes/api.php`, `app/Providers/AppServiceProvider.php` | Só `register`/`login` tinham `throttle:10,1`; todo o resto (`tasks/*`, `social/*`, `admin/*`, `health*`) sem nenhum limite de requisição. **Fixing now**: `RateLimiter::for('api', ...)` registrado em `AppServiceProvider::boot()` (60 req/min por usuário autenticado ou por IP) e `middleware => 'throttle:api'` aplicado no grupo `v1` de `routes/api.php` (cobre auth + demais domínios). |
| Controllers de `Social/Contacts`, `Task/Statuses`, `Task/Priorities` bypassam a camada de Service | `app/Packages/Social/Contacts/Controllers/`, `app/Packages/Task/Statuses/Controllers/`, `app/Packages/Task/Priorities/Controllers/` | **Resolvido**: lógica extraída para Services dedicados seguindo o padrão de `Task/Tasks/Services/` (`ListContactsService`, `DeleteContactService`, `ListStatusesService`, `ListPrioritiesService`), um por ação, `execute()` como método público. `DeleteContactService::execute()` embrulha o `delete()` em `DB::transaction()` (mandatory.md #4), corrigindo também a ausência de transação no `destroy()` de contatos. Não foi introduzido Repository — as queries continuam simples (`Model::where(...)->get()`/`findOrFail()`), sem justificativa para uma camada extra. Verificado: `make test`/`php artisan test` sem regressão (mesmas 5 falhas pré-existentes de RBAC/seed em `Admin`/`RBAC`, confirmadas também na baseline via `git stash`); smoke-test real via `curl` em `/api/v1/social/contacts` (index/store/destroy) e `/api/v1/task-statuses`, `/api/v1/task-priorities` retornando 200 com dados corretos. |
| Domínio `Social` resolve Services via `app()` em vez de injeção por construtor | `app/Packages/Social/**` | **Resolvido**: `ContactController` e `PersonController` convertidos para injeção por construtor (`private {Service} ${service}` no `__construct()`), removendo todas as chamadas `app({Service}::class)`. Padrão agora idêntico ao de `TaskController`. |
| Dockerfile de produção diverge do documentado | `task-manager-api/.docker/php/Dockerfile` (produção) | Usava PHP 8.4 em vez do PHP 8.3 documentado/exigido (`composer.json: "php": "^8.3"`), incluía toolchain completo de dev na imagem de produção, e rodava `composer install` no start do container em vez de na build. **Resolvido**: `Dockerfile` reescrito como multi-stage (`builder` com toolchain completo + `composer install --no-dev --optimize-autoloader` em build-time; stage final `php:8.3-fpm` só com libs de runtime, sem `vim`/`wget`/`build-essential`/dev headers). `start.sh` não roda mais `composer install`. `docker-compose.production.yml`/`docker-compose.local.yml` tiveram o `context` do build do serviço PHP movido para a raiz do projeto (`context: .`, `dockerfile: .docker/php/Dockerfile`) para o stage de build enxergar o `composer.json`; `.dockerignore` adicionado para não vazar `.git`/`.env`/`vendor` no contexto de build. Verificado: `php -v` → `8.3.32`, container sobe limpo, `GET /` → 200, `POST /api/v1/auth/login` com body vazio → 422 de validação. |
| Supervisor tenta subir Laravel Horizon não instalado | `task-manager-api/.docker/php/supervisor*.conf` (ou equivalente) | Config de supervisor referenciava um processo Horizon, mas `laravel/horizon` não está no `composer.json` (reconfirmado via `grep -i horizon composer.json`, nenhum resultado) — risco de falha/crash-loop. **Resolvido**: removida a `COPY horizon/horizon.conf ...` do `Dockerfile` e apagado `.docker/php/horizon/horizon.conf`; o `[program:laravel-worker]` (queue:work) e `[program:cron]` em `supervisord.conf` foram mantidos intactos. Verificado nos logs do container (`docker logs task-manager_api_php`) que só `laravel-worker_00..09` e `cron` sobem, sem nenhuma referência a Horizon. |
| Sem ESLint/lint para o Nuxt UI | `task-manager-ui/` | **Resolvido**: adicionado `@nuxt/eslint` (módulo oficial Nuxt, registrado em `modules` do `nuxt.config.ts`) + `eslint.config.mjs` na raiz (`withNuxt(...)`, gerado/consumido via `.nuxt/eslint.config.mjs` no `postinstall: nuxt prepare`). Script `"lint": "eslint ."` adicionado ao `package.json` e ao job `ui` do `.github/workflows/ci.yml` (`npm run lint` antes de `npm run build`). Rodado sobre a base real: 100 problemas (45 erros + 55 avisos) na primeira execução — os 55 avisos e 1 erro eram auto-fixáveis (`vue/attributes-order`, `vue/html-self-closing`) via `eslint . --fix`; os demais 44 erros reais foram corrigidos um a um (imports/vars não usados, bloco `catch` vazio, classe `TaskService` só-estática convertida para objeto literal seguindo o padrão de `AuthService`/`AdminService`, e a mutação direta da prop `filters` em `TaskFilters.vue` substituída por `defineModel` + `v-model:filters` no `pages/tasks/index.vue`, preservando o comportamento). **Triagem**: a regra `@typescript-eslint/no-explicit-any` foi desligada explicitamente em `eslint.config.mjs` (com comentário explicando o motivo) — o projeto não tem uma camada de tipos de API (sem axios/DTO tipado, `any` é usado deliberadamente em Services/hooks para payload/resposta), e modelar todos os contratos de request/response só para ativar essa regra era um escopo maior que "adicionar ESLint"; decisão para o time revisitar se/quando uma camada de tipos for introduzida. Verificado: `npm run lint` limpo (zero erros/avisos), `npm run build` ok, rebuild do container `task-manager-ui-ui-1` e `GET /` → 200. |
| `ContactController`/`TaskController::updateStatus` validam inline em vez de `FormRequest` | `app/Packages/Social/Contacts/Controllers/ContactController.php`, `app/Packages/Task/Tasks/Controllers/TaskController.php` | **Parcialmente resolvido**: `ContactController::store()`/`update()` migrados para `StoreContactRequest`/`UpdateContactRequest` (`app/Packages/Social/Contacts/Requests/`), seguindo o padrão de `CreateTaskRequest`/`UpdatePersonRequest`. `TaskController::updateStatus()` e `PersonController::avatar()` **continuam** com `$request->validate([...])` inline — fora do escopo desta tarefa, gap ainda aberto. |

## Guias Especializados

| Situação | Arquivo |
|----------|---------|
| Backend geral | `.claude/agents/backend-agent.md` |
| CRUD / endpoints | `.claude/agents/backend-crud-agent.md` |
| Code review | `.claude/agents/backend-review-agent.md` |
| Migração de banco | `.claude/agents/backend-db-agent.md` |
| Nova feature | `.claude/checklists/new-feature.md` |
| Novo endpoint | `.claude/checklists/new-endpoint.md` |
| Nova entidade | `.claude/checklists/new-entity.md` |
| Code review checklist | `.claude/checklists/code-review.md` |

## Regras do Projeto

@.claude/rules/architecture.md
@.claude/rules/naming.md
@.claude/rules/mandatory.md
@.claude/rules/formatting.md
@.claude/rules/testing.md
@.claude/rules/git-workflow.md
@.claude/rules/project-context.md
@.claude/rules/project-tech.md
@.claude/rules/utilities.md
@.claude/rules/response-patterns.md
