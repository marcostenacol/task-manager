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
| DTO | **Nenhum uso real hoje.** `spatie/laravel-data: ^4.21` está no `composer.json`, mas nenhuma classe do domínio estende `Spatie\LaravelData\Data` (`grep -rl "Spatie\\LaravelData" app` não retornou nada) — Services recebem `array` puro (`$request->validated()`) direto. Ver `rules/mandatory.md`. |
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
4. **DTO**: não há uso real de `Spatie\LaravelData\Data` no projeto hoje, apesar do pacote instalado — Services recebem `array` (`$request->validated()`). Não introduza DTO novo sem alinhar com o time; se for pedido explicitamente, `Spatie\LaravelData\Data` é o pacote disponível.
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
| `BaseController` é scaffold morto | `app/Base/Http/Controllers/BaseController.php` | Nenhum Controller de domínio o estende; todos estendem `Illuminate\Routing\Controller` + `use Response` direto. Duas formas de fazer a mesma coisa convivendo sem necessidade. Ver `rules/architecture.md`. |
| Camada de Repository inconsistente | `app/Packages/*/Repositories/` | Repository é opcional e a escolha de quando usá-lo não é documentada por regra objetiva — hoje é "critério do autor original" (query simples vs. complexa). Risco: dois desenvolvedores decidem diferente para o mesmo tipo de caso. Ver `rules/architecture.md`. |
| `spatie/laravel-data` instalado e não usado | `composer.json` | Pacote no `require`, zero classes do domínio o estendem. Dependência morta ou feature futura não iniciada — não está claro qual. Ver `rules/mandatory.md`. |
| `routes/api.php` com FQCN inline | `routes/api.php` | Viola a própria convenção de import do projeto (`use` no topo) em várias rotas de `social`/`admin`. Arquivo único e grande, sem separação por domínio/glob. Ver `rules/naming.md`. |
| Nomes de método da trait `Response` divergem de outros projetos AE3 | `app/Base/Traits/Response.php` | `returnError()` em vez de `errorResponse()`; `invalidArgumentResponse()` retorna 400 em vez de 422. Risco real de um agente/dev replicar por hábito o nome de outro projeto e quebrar silenciosamente. Ver `rules/utilities.md`. |
| `Response` trait usa status HTTP como número literal internamente | `app/Base/Traits/Response.php` | A própria base do projeto (`404`, `500`, `400` hardcoded) viola a regra que pede uso de `Response::HTTP_*` em código novo — inconsistência entre a regra pregada e a base real. Ver `rules/mandatory.md`. |
| Guard `web` de sessão no `config/auth.php` sem uso real | `config/auth.php` | Resíduo do skeleton padrão do Laravel — a API real é 100% Bearer token via `auth.api`/PL-pgSQL, nenhuma rota usa o guard `web`. Configuração morta que pode confundir quem for investigar auth. Ver `rules/project-tech.md`. |
| Sem testes `Unit/` reais | `tests/Unit/` | Só o esqueleto padrão do Laravel; toda a cobertura real é via `Feature/` (HTTP+banco). Regra de negócio isolada (cálculo, validação) não tem exemplo de teste unitário puro no repositório. Ver `rules/testing.md`. |
| Convenção de commit heterogênea | histórico git | Mistura de Conventional Commits puro e prefixos `[REEST]`/`[SECURITY]`/`[TASK-REEST]` por fase de projeto, sem uma convenção única vigente. Ver `rules/git-workflow.md`. |
| Sem template de PR | `.github/` | Não encontrado nenhum template real de PR no repositório. Ver `rules/git-workflow.md`. |
| Fila sempre síncrona | `.env`, `QUEUE_CONNECTION=database` | Sem worker ativo em dev — qualquer `Job` despachado roda sincronamente na mesma request. Risco de mascarar lentidão/erros de fila até produção. Ver `rules/project-context.md`. |
| `TaskRepository::listWithFilters()` mistura SQL cru (CTE) com padrão Eloquent do resto do domínio | `app/Packages/Task/Tasks/Repositories/TaskRepository.php` | Paginação é montada manualmente a partir de `DB::select()`, não `->paginate()` nativo — assimetria de padrão de listagem entre domínios que usam CTE e os que não usam. Ver `rules/response-patterns.md`. |
| Sem CI/CD | (ausência de `.github/workflows/`, `.gitlab-ci.yml`, `Jenkinsfile`) | Nenhum pipeline configurado — nem para a API (Pint/Pest), nem para a UI (lint/build). Testes reais existem (Pest, cobertura de Feature) mas não são executados automaticamente em push/PR hoje. |
| `APP_DEBUG=true` no `.env` real de produção | `task-manager-api/.env` (não versionado) | Servidor de produção rodava com debug ligado — stack trace completo exposto em qualquer erro 500. **Fixing now**: alterado para `false`; `LOG_LEVEL` (`debug`→`error`) e `SHOW_ERROR_MESSAGE` (`true`→`false`) também ajustados no mesmo arquivo; container `task-manager_api_php` reiniciado para aplicar. |
| Sem `config/cors.php` explícito | `task-manager-api/config/` | Sem config própria, a API caía no default permissivo do pacote CORS do Laravel (todas as origens). **Fixing now**: criado `config/cors.php` com `allowed_origins` restrito às origens reais desta implantação (`tarefas.mvndev.online`, `150.230.75.122`), `supports_credentials = false`. |
| Nginx sem headers de segurança | `task-manager-api/.docker/nginx/conf.d/*.conf` (dev/hml/local/prod) | Nenhum `X-Content-Type-Options`/`X-Frame-Options`/`Referrer-Policy` em nenhum dos 4 arquivos. **Fixing now**: adicionados os três headers no nível do `server{}` (não só em `location /`, para cobrir também as respostas via `location ~ \.php$`) nos 4 arquivos. HSTS **não** adicionado de propósito — a terminação HTTPS deste ambiente está em setup externo em andamento (Caddy, portas 80/443), fora do escopo desta tarefa. |
| Sem rate limiting global na API | `task-manager-api/routes/api.php`, `app/Providers/AppServiceProvider.php` | Só `register`/`login` tinham `throttle:10,1`; todo o resto (`tasks/*`, `social/*`, `admin/*`, `health*`) sem nenhum limite de requisição. **Fixing now**: `RateLimiter::for('api', ...)` registrado em `AppServiceProvider::boot()` (60 req/min por usuário autenticado ou por IP) e `middleware => 'throttle:api'` aplicado no grupo `v1` de `routes/api.php` (cobre auth + demais domínios). |
| Controllers de `Social/Contacts`, `Task/Statuses`, `Task/Priorities` bypassam a camada de Service | `app/Packages/Social/Contacts/Controllers/`, `app/Packages/Task/Statuses/Controllers/`, `app/Packages/Task/Priorities/Controllers/` | Consultam o Model Eloquent diretamente no Controller, violando a Regra de Ouro 5/`mandatory.md` #1-2 (Controller não deveria conhecer banco/query). Diferente do padrão real de `Task/Tasks` (Controller→Service→Model). Gap novo, não corrigido nesta tarefa (fora do escopo de segurança). |
| Domínio `Social` resolve Services via `app()` em vez de injeção por construtor | `app/Packages/Social/**` | Viola a Regra de Ouro 10/`mandatory.md` #13 (que já tolera `app()` só dentro de `AuthenticateMiddleware`, não em Controllers/Services de domínio). Gap novo, não corrigido nesta tarefa. |
| Dockerfile de produção diverge do documentado | `task-manager-api/.docker/php/Dockerfile` (produção) | Usa PHP 8.4 em vez do PHP 8.3 documentado/exigido (`composer.json: "php": "^8.3"`), inclui toolchain completo de dev na imagem de produção, e roda `composer install` no start do container em vez de na build. Gap novo, não corrigido nesta tarefa. |
| Supervisor tenta subir Laravel Horizon não instalado | `task-manager-api/.docker/php/supervisor*.conf` (ou equivalente) | Config de supervisor referencia um processo Horizon, mas `laravel/horizon` não está no `composer.json` — falha (ou fica em crash loop) na subida do container. Gap novo, não corrigido nesta tarefa. |
| Sem ESLint/lint para o Nuxt UI | `task-manager-ui/` | Nenhuma ferramenta de lint configurada (`package.json` não tem `eslint`/config associada). Gap novo, não corrigido nesta tarefa. |
| `ContactController`/`TaskController::updateStatus` validam inline em vez de `FormRequest` | `app/Packages/Social/Contacts/Controllers/ContactController.php`, `app/Packages/Task/Tasks/Controllers/TaskController.php` | Usam `$request->validate([...])` direto no Controller em vez de uma classe `{Ação}{Entidade}Request` dedicada, inconsistente com o padrão real do resto do domínio `Task` (`CreateTaskRequest`, `UpdateTaskRequest`). Gap novo, não corrigido nesta tarefa. |

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
