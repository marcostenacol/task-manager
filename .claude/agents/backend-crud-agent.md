# Agente — CRUD / Endpoints (`task-manager-api`)

Use como referência ao criar um endpoint novo de CRUD, com base no padrão real e completo do domínio `Task/Tasks` (o exemplo mais completo do repositório: index, store, show, update, destroy, updateStatus).

## Passo a passo (espelhando `Task/Tasks`)

1. **Rota** em `routes/api.php`, dentro do grupo `v1` já existente, com `middleware('auth.api')` (ou `auth.api:{permissão}` se a ação exigir ability específica, ver rotas de `admin.users`). Nomeie a rota (`->name('index')` etc.) — confirme se o grupo já tem `'as' => '{recurso}.'` antes de decidir o nome completo.
2. **Model**: `app/Packages/{Domínio}/{Subdomínio}/Models/{Entidade}.php` — `HasUuids`, `$incrementing = false`, `$keyType = 'string'`, `$table` com schema qualificado, `$fillable`, `casts()` como método (não propriedade `$casts`, ver `Task.php`), relacionamentos `BelongsTo`/`HasMany` tipados.
3. **Migration**: `Schema::create('{schema}.{tabela}', ...)`, FK nomeada (`$table->foreign('x_id', 'fk_{tabela}_{coluna}')`), `down()` com `dropIfExists`.
4. **Request** por ação (`Create{Entidade}Request`, `Update{Entidade}Request`): `authorize() => true`, `rules()` com `exists:` contra a tabela certa (schema-qualificado com prefixo `pgsql.` se for fora de `public` — ver `rules/utilities.md`). Não sobrescrever `failedValidation()`.
5. **Service** por ação (`Create{Entidade}Service`, `Update{Entidade}Service`, `Delete{Entidade}Service`, `Detail{Entidade}Service`, `List{Entidade}sService`): `execute()`, `DB::transaction()` em escrita, retorna o `Resource` já construído (não array/Model cru).
6. **Repository** — só se a listagem tiver filtro dinâmico/CTE (ver `TaskRepository::listWithFilters()` como referência de binding seguro); para CRUD simples, chame o Model direto na Service.
7. **Resource**: `extends JsonResource`, só `toArray()`.
8. **Controller**: um método por ação, injeta o Service certo no método (não no construtor), `try/catch` com `self::returnError($e)`.
9. **Teste Feature**: replique a estrutura de `tests/Feature/Task/TaskTest.php` — login real no `beforeEach`, dados de referência por seeder/slug, `withToken($this->token)->postJson(...)`, assert de `success`/campos de `data`, e `assertDatabaseHas`.

## Checklist rápido

- [ ] Rota nomeada, com middleware de auth (+ ability se aplicável)
- [ ] Migration com schema qualificado e `down()`
- [ ] Model com UUID (`HasUuids`) se for entidade de domínio nova
- [ ] Request com `authorize() => true`, sem `failedValidation()` redundante
- [ ] Service = uma ação, `execute()`, `DB::transaction()` em escrita
- [ ] Resource simples, `toArray()` só
- [ ] Controller com `try/catch` → `self::returnError($e)`
- [ ] Teste Feature Pest cobrindo o caminho feliz e ao menos um caminho infeliz
- [ ] Pint rodado nos arquivos alterados
