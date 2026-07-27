# Agente — Banco de dados / Migrations (`task-manager-api`)

## Convenções reais confirmadas

- Migration anônima: `return new class extends Migration { ... };` (padrão real, ver todas as migrations de `database/migrations/`).
- Nome de arquivo: `YYYY_MM_DD_HHMMSS_create_{tabela}_table.php`, `..._add_{coluna}_to_{tabela}_table.php`, `..._seed_{tabela}_table.php` (há migrations reais dedicadas só a seed, ex.: `2026_04_09_085220_seed_roles_table.php`) — este projeto usa migration para rodar seed também, mesmo padrão de "seed via migration dedicada" visto em outros projetos Laravel da empresa.
- Schema qualificado sempre: `Schema::create('admin.users', ...)`, `Schema::create('public.tasks', ...)` — mesmo tabelas em `public` são qualificadas explicitamente no código real (`Task.php`: `protected $table = 'public.tasks';`).
- Criação de schema Postgres nomeado é feita via migration dedicada com `DB::statement('CREATE SCHEMA IF NOT EXISTS admin;')`, com `down()` fazendo `DROP SCHEMA IF EXISTS admin CASCADE;` (ver `2026_04_09_084915_create_admin_schema.php`). `public` não precisa desse passo.
- FK nomeada: `$table->foreign('role_id', 'fk_users_role_id')->references('id')->on('admin.roles');` — padrão de nome confirmado: `fk_{tabela}_{coluna}` (não `{tabela}_{coluna}_fk` como em outros projetos AE3 — a ordem do prefixo é diferente aqui, confirme antes de nomear).
- UUID como chave primária é o padrão para entidades de domínio (`$table->uuid('id')->primary();`, visto em `create_users_table`) — combine com `HasUuids` no Model.
- Há migrations reais que criam **funções PL/pgSQL** (`2026_04_09_090808_create_auth_flux.php`, `2026_04_09_090809_refactor_auth_functions_and_fix_types.php`, `2026_05_05_211800_update_get_user_by_token_function.php`) — esse é um padrão real deste projeto (autenticação delegada a procedure de banco), não um exagero a evitar; se for tocar o fluxo de auth, uma migration nova de função PL/pgSQL é consistente com o padrão observado.
- Soft delete: `2026_05_26_000000_add_soft_delete_to_tasks_table.php` — `Task` usa `SoftDeletes` no Model correspondente.

## Rodando migrations

```bash
make migrate            # ambiente normal
make migrate-testing    # --env=testing --force, banco de teste
make db-testing         # dropa e recria o banco de teste via psql direto no container pgsql
```

## Seeders

Seeders reais em `database/seeders/` (`AdminUserSeeder`, `PermissionSeeder`, `RoleSeeder`, `SettingSeeder`, `TaskPrioritySeeder`, `TaskStatusSeeder`, `UserStatusSeeder`) são chamados por **migrations dedicadas** (`2026_04_09_085220_seed_roles_table.php` etc.), não só pelo `DatabaseSeeder`. Ao adicionar um seeder novo de dado de referência (ex.: um novo status/prioridade), siga o mesmo padrão: seeder idempotente (`firstOrCreate`/`updateOrCreate`) + migration dedicada que o chama — não assuma que registrar no `DatabaseSeeder` sozinho é suficiente para o dado chegar em produção (não foi confirmado se `db:seed` roda no pipeline de deploy deste projeto; siga o padrão de migration dedicada, que é o observado de forma consistente em ~10 migrations reais).
