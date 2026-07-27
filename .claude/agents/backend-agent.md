# Agente — Backend geral (`task-manager-api`)

Guia de referência rápida para qualquer tarefa de backend neste projeto. Leia primeiro `.claude/CLAUDE.md` e `.claude/rules/architecture.md`.

## Antes de codificar

1. Identifique o domínio (`Task`, `Admin/Users`, `Social/*`, `Auth`) — código novo sempre em `app/Packages/{Domínio}/{Subdomínio}/{Camada}/`. Não existe legado flat neste projeto para "preservar"; se você está tentado a criar algo fora de `app/Packages/`, pare e reconsidere.
2. Leia pelo menos um Controller/Service/Model real do domínio que você vai tocar (ou de um domínio irmão, se o seu é novo) antes de escrever código — o padrão real diverge sutilmente do "livro de receitas" ideal (ver `rules/architecture.md`, seção sobre Repository opcional e `BaseController` não usado).
3. Decida se precisa de Repository: só se a query for complexa/reaproveitada (CTE, filtros dinâmicos, paginação manual). Caso contrário, chame o Model do Eloquent direto na Service.

## Ao escrever

- Controller: `extends App\Http\Controllers\Controller` + `use App\Base\Traits\Response;`. Todo método com `try/catch` retornando `self::returnError($e)` no catch (não `errorResponse`).
- Service: uma classe por ação, método `execute()`, `DB::transaction()` em qualquer escrita.
- Model: `HasUuids` + `$incrementing = false` + `$keyType = 'string'` para entidades de domínio novas; `$table` sempre com schema qualificado (mesmo `public.*`, ver `Task.php`).
- Request: `authorize()` sempre `true`; **não precisa sobrescrever `failedValidation()`** — o handler global em `bootstrap/app.php` já despacha `ValidationException` para `Response::failedValidationResponse()`.
- Mensagens ao usuário em português; identificadores em inglês.

## Antes de finalizar

1. Rode o Pint no(s) arquivo(s) alterado(s) (`rules/formatting.md`).
2. Se a mudança tocar um endpoint HTTP, escreva/atualize um teste Pest de Feature seguindo o padrão de `tests/Feature/Task/TaskTest.php` (login real no `beforeEach`, `DatabaseTransactions`, `withToken()`).
3. Rode `make test f={filtro}` para confirmar.
4. Se mudou contrato de rota/request/response, `dedoc/scramble` gera o OpenAPI por inferência automaticamente — não há passo manual de anotação a lembrar aqui (diferente de projetos com `vyuldashev/laravel-openapi`).
