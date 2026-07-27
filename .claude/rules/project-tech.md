# Configuração Técnica — Task Manager

## Containers Docker (confirmado no `Makefile`)

| Variável do Makefile | Valor | Uso |
|---|---|---|
| `DOCKER_SERVICE_PHP_FPM` | `task-manager-php-fpm` | PHP-FPM — nunca execute PHP direto no host |
| `DOCKER_SERVICE_NGINX` | `task-manager-nginx` | Servidor web |
| `DOCKER_SERVICE_PGSQL` | `pgsql` | PostgreSQL |
| `PREFIX`/`COMPOSE_PROJECT_NAME` | `task-manager` | Prefixo dos containers |

Compose file: `docker-compose.local.yml` (dev) ou `docker-compose.production.yml` quando `APP_ENV=production` (seleção automática no `Makefile`).

## Comandos (todos confirmados no `Makefile` real)

```bash
make build / up / down / restart / logs / logs-php / logs-nginx
make shell-php / shell-nginx / in
make art c="migrate:status"
make composer c="require pacote"
make npm c="install"
make php c="-v"
make migrate
make migrate-testing
make db-testing
make test [f=NomeDoTeste]
```

## Organização de domínios

100% modular, sem legado flat (ver `architecture.md`):

```
app/Packages/{Domínio}/{Subdomínio}/
    Controllers/
    Services/
    Repositories/    (quando a query justifica)
    Models/
    Requests/
    Resources/
    Enum/
    Middlewares/
```

## Schemas PostgreSQL

| Schema | Conteúdo confirmado |
|--------|----------------------|
| `admin` | `admin.users`, `admin.roles`, `admin.user_statuses`, `admin.permissions`, `admin.role_has_permissions`, `admin.settings`, `admin.personal_access_tokens`, `admin.refresh_tokens` |
| `public` | `public.tasks`, `public.task_statuses`, `public.task_priorities`, `public.user_contacts`, tabelas padrão do Laravel (`cache`, `jobs`, `sessions`) |

Migration `2026_04_09_084915_create_admin_schema.php` cria o schema `admin` explicitamente via `DB::statement('CREATE SCHEMA IF NOT EXISTS admin;')` — `public` é o schema-padrão do Postgres, não precisa de criação.

## Autenticação (real, sem Sanctum/Passport/JWT-lib)

- Middleware: `App\Packages\Auth\Auth\Middlewares\AuthenticateMiddleware`, alias `auth.api` (registrado em `bootstrap/app.php`).
- Guard configurado em `config/auth.php`: `'web' => ['driver' => 'session', 'provider' => 'users']`, com `'users'` apontando para `App\Packages\Admin\Users\Models\User` — esse guard de sessão **não é o mecanismo real usado pela API** (que é 100% Bearer token via `auth.api`); nenhuma rota de `routes/api.php` o usa. **Não é removível, porém** (investigado 2026-07-27): `resources/views/welcome.blade.php`, ainda servida em `GET /`, usa `@auth`/`@endauth`, que resolve o guard default em toda renderização — remover o guard `web` quebra a rota raiz com `"Auth guard [web] is not defined."`. É dead code de negócio, mas dependência dura do framework enquanto a view padrão existir.
- Token validado contra função PL/pgSQL (ver `database/migrations/2026_04_09_090808_create_auth_flux.php`, `..._090809_refactor_auth_functions_and_fix_types.php`, `..._211800_update_get_user_by_token_function.php`).
- Expiração: `SettingsEnum::TOKEN_EXPIRATION_MINUTES` (configurável via tabela `admin.settings`, não hardcoded).

## Dependências principais (`composer.json`)

| Pacote | Versão | Uso confirmado |
|---|---|---|
| `laravel/framework` | `^13.0` | Framework |
| `dedoc/scramble` | `*` | Geração automática de OpenAPI por inferência (não anotação manual) |
| `predis/predis` | `^2.0` | Cliente Redis |
| `lucascudo/laravel-pt-br-localization` | `^3.0` | Mensagens de validação em pt-BR |
| `pestphp/pest` + `pest-plugin-laravel` | `^4.4` / `^4.1` | Testes (com cobertura real) |
| `laravel/pint` | `^1.27` | Formatação |

## Frontend (`task-manager-ui`) — Nuxt 4

| Pacote | Versão |
|---|---|
| `nuxt` | `^4.4.4` |
| `vue` | `^3.5.33` |
| `vue-router` | `^5.0.6` |

Sem Pinia, sem axios, sem UI kit — `package.json` só lista essas três dependências de runtime. `srcDir: 'app/'`, `runtimeConfig.public.apiBase` (default `http://localhost:8000/api`, via `NUXT_PUBLIC_API_BASE`).

## Testes

Pest 4, real e com cobertura ativa (ver `testing.md`). Banco de teste dedicado (`{DB_DATABASE}_testing`), gerenciado via `make db-testing`/`make migrate-testing` — **não** `RefreshDatabase` por teste, e sim uma migração única + `DatabaseTransactions` por teste.
