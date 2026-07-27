# Checklist — Code Review

Ver `agents/backend-review-agent.md` para os pontos de atenção específicos deste projeto (nomes de método reais, padrões que divergem de outros projetos Laravel/AE3). Checklist objetivo:

## Arquitetura
- [ ] Código novo em `app/Packages/{Domínio}/{Subdomínio}/{Camada}/` — nenhum arquivo novo fora desse padrão.
- [ ] Controller sem lógica de negócio nem query.
- [ ] Service = uma ação, `execute()`, `DB::transaction()` em qualquer escrita.
- [ ] Repository presente só se a query justificar (não exigir por hábito).

## Nomenclatura e convenções reais
- [ ] Erros tratados com `self::returnError($e)` (não `errorResponse`).
- [ ] Controller `extends Controller` (Laravel padrão) + `use Response;` (não `BaseController`).
- [ ] Model de entidade de domínio com UUID (`HasUuids`), `$table` schema-qualificado, `$fillable`.
- [ ] Identificadores em inglês, mensagens ao usuário em português.
- [ ] `use` no topo do arquivo, sem FQCN inline em código novo (rotas existentes já violam isso — não é motivo de reprovação retroativa, mas linha nova deve seguir o padrão correto).

## Segurança e dados
- [ ] Nenhuma interpolação de valor de usuário em SQL cru — bindings nomeados sempre, mesmo em `IN (...)`.
- [ ] `exists:`/`unique:` contra tabela de schema não-`public` usa prefixo `pgsql.{schema}.{tabela}`.
- [ ] Permissão nova é dedicada por ação (allow-list), não reaproveitada de outra rota.
- [ ] Nenhum dado pessoal novo (perfil/contato) adicionado sem confirmação de finalidade.

## Testes
- [ ] Endpoint novo/alterado tem teste Feature Pest cobrindo status, `success`, shape de `data`.
- [ ] Teste segue o padrão real (`DatabaseTransactions`, login real no `beforeEach`, `withToken()`) — não introduzir mock de autenticação sem necessidade.

## Formatação
- [ ] Pint rodado nos arquivos `.php` alterados.

## Frontend (se aplicável)
- [ ] Nenhuma dependência nova (Pinia, axios, UI kit) introduzida sem alinhamento — `package.json` hoje só tem `nuxt`/`vue`/`vue-router`.
- [ ] Componente novo posicionado em `app/modules/{domínio}/components/` (auto-importado) ou `app/components/` conforme o escopo (transversal vs. de domínio).
