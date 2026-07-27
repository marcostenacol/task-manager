# Checklist — Nova feature

1. [ ] Identificar o domínio em `app/Packages/{Domínio}/{Subdomínio}/` — criar a pasta se for domínio novo, seguindo a mesma estrutura de `Task/Tasks` (`Controllers/`, `Services/`, `Models/`, `Requests/`, `Resources/`, e `Repositories/` só se necessário).
2. [ ] Ler pelo menos um domínio irmão real de ponta a ponta antes de escrever (ver `rules/architecture.md`).
3. [ ] Migration(s) com schema qualificado, FK nomeada (`fk_{tabela}_{coluna}`), `down()` correto.
4. [ ] Seeder de dado de referência (se aplicável), idempotente, chamado por migration dedicada (ver `agents/backend-db-agent.md`).
5. [ ] Model com UUID (`HasUuids`, `$incrementing = false`, `$keyType = 'string'`), `$table` qualificado, `$fillable`, `casts()`.
6. [ ] Service por ação, `execute()`, `DB::transaction()` em escrita, retorno tipado (Resource, não array cru).
7. [ ] Request com `authorize() => true`, regras com `exists:`/`unique:` corretas (prefixo `pgsql.` se fora de `public`).
8. [ ] Controller: método por ação, injeta Service no método, `try/catch` → `self::returnError($e)`.
9. [ ] Rota em `routes/api.php`, dentro do grupo `v1`, com `auth.api` (+ ability se aplicável), nomeada.
10. [ ] Teste Feature Pest (login real no `beforeEach`, `DatabaseTransactions`, caminho feliz + infeliz).
11. [ ] Pint nos arquivos alterados.
12. [ ] Se mudou contrato HTTP, nada manual a fazer no OpenAPI (Scramble infere automaticamente) — só confirme que rota/Request/Resource estão bem tipados para a inferência funcionar.
13. [ ] Se a feature tocar frontend: confirmar o padrão real de client HTTP do módulo Nuxt correspondente antes de escrever (`app/modules/{domínio}/services/`).
