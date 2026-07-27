# Agente — Code Review (`task-manager-api` / `task-manager-ui`)

Ver `.claude/checklists/code-review.md` para o checklist objetivo. Este arquivo documenta os pontos de atenção específicos deste projeto que um revisor genérico de Laravel não saberia sem ler o código real.

## Pontos específicos deste projeto (não assuma padrão de outro projeto Laravel/AE3)

- **`self::errorResponse()` não existe aqui** — o nome real é `self::returnError()`. Se um PR introduzir `errorResponse()`, é um erro de nome, não uma escolha válida.
- **`BaseController` não é a base real** dos Controllers — se um PR fizer um Controller novo `extends App\Base\Http\Controllers\BaseController`, questione: nenhum Controller real do domínio faz isso hoje; o padrão real é `extends App\Http\Controllers\Controller` + `use Response;`.
- **Repository não é obrigatório** — não peça "onde está o Repository?" em todo PR de Service nova; primeiro cheque se a query é simples (Model direto é aceitável) ou complexa (Repository esperado).
- **DTO (`Spatie\LaravelData\Data`) não é usado hoje** — se um PR introduzir DTO numa Service nova sem que o time tenha pedido isso, é uma mudança de padrão de fato, não apenas seguir "boas práticas" — sinalize para alinhamento, não aprove/reprove sozinho.
- **`FormRequest` não precisa de `failedValidation()` customizado** — o handler global já trata `ValidationException`. Um PR que adiciona esse boilerplate por hábito de outro projeto não está errado, mas é redundante — aponte a redundância.
- **UUID como PK** é o padrão esperado em entidade de domínio nova — um Model novo com `bigIncrements`/`id()` auto-incremento sem justificativa é uma divergência a questionar.
- **Bindings de SQL cru**: se um PR tiver `DB::select()`/`DB::statement()` com qualquer interpolação de valor de usuário na string (mesmo dentro de um `IN (...)`), é bloqueante — o padrão correto e já demonstrado no código real é `TaskRepository::listWithFilters()` (fragmento de coluna pode ser concatenado condicionalmente, valor nunca).
- **Schema qualificado no `$table`**: cheque mesmo para tabelas em `public` — o padrão real qualifica explicitamente (`'public.tasks'`), não deixa implícito.
- **Mensagens em português, identificadores em inglês** — checagem rápida em qualquer Controller/Service novo.
- **Rotas com FQCN inline**: `routes/api.php` já tem essa violação em vários pontos — não é motivo para reprovar um PR que segue o padrão existente do arquivo, mas se o PR adicionar rota nova, prefira sugerir `use` no topo para a linha nova.

## Frontend (`task-manager-ui`)

- Verifique se a chamada à API segue o padrão já existente em `app/modules/{domínio}/services/` daquele domínio específico antes de introduzir Pinia/axios/outra lib — nenhuma dessas está no `package.json` hoje; adicionar uma delas é uma mudança de dependência que merece discussão, não uma decisão de PR isolado.
