# Checklist — Nova entidade

1. [ ] Decidir schema Postgres: `admin` (dados administrativos/sistema) ou `public` (domínio de negócio) — ambos já qualificam `$table` explicitamente mesmo em `public`.
2. [ ] Migration `Schema::create('{schema}.{tabela}', ...)` com `$table->uuid('id')->primary();` (padrão real de PK), colunas `snake_case`, FKs nomeadas (`fk_{tabela}_{coluna}`), `down()` com `dropIfExists`.
3. [ ] Model em `app/Packages/{Domínio}/{Subdomínio}/Models/{Entidade}.php`: `use HasUuids;`, `$incrementing = false`, `$keyType = 'string'`, `$table` qualificado, `$fillable`, `casts(): array` (método, não propriedade `$casts` — ver `Task.php`), relacionamentos tipados (`BelongsTo`, `HasMany`).
4. [ ] `SoftDeletes` se a entidade precisar de exclusão lógica (padrão real em `Task`, adicionado via migration dedicada `add_soft_delete_to_tasks_table`).
5. [ ] Factory em `database/factories/` se a entidade for usada em teste (ver `UserFactory.php` como único exemplo real hoje — confirme se sua entidade nova precisa de uma antes de criar dados via `Model::create()` solto em teste).
6. [ ] Seeder de dado de referência, se aplicável, idempotente + migration dedicada que o chama.
7. [ ] Repository só se antecipar query complexa/filtro dinâmico — senão, Services chamam o Model direto.
8. [ ] Resource, Request, Service e Controller seguindo `checklists/new-feature.md`.
9. [ ] Teste Feature cobrindo a entidade nova.
