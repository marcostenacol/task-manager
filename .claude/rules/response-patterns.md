# Padrões de Response

## Envelope padrão

```json
{ "success": true, "message": "Tarefa criada com sucesso.", "data": { ... } }
```

Confirmado em todos os métodos de `TaskController` — sempre `self::successResponse($data, 'mensagem em pt-BR', $status_code)`.

## Sucesso com Resource único

```php
return self::successResponse($data, 'Tarefa recuperada com sucesso.');
```

Onde `$data` já é o retorno do Service (`TaskResource`/`AdminUserResource` etc.) — o Service devolve o Resource já construído, o Controller não constrói o Resource ele mesmo (diferente de outros projetos AE3 onde o Controller monta `Resource::make($service->execute(...))`). Padrão real aqui: **o Service retorna o Resource pronto**, ver `CreateTaskService::execute(): TaskResource`.

## Sucesso sem dado de retorno (delete)

```php
return self::successResponse(null, 'Tarefa excluída com sucesso.');
```

Confirmado em `TaskController::destroy()`.

## Listagem — `ListTasksService` retorna estrutura já paginada

`TaskRepository::listWithFilters()` devolve um `LengthAwarePaginator` construído manualmente a partir do resultado de `DB::select()` (porque a query é uma CTE crua, não um `Eloquent\Builder`, então não há `->paginate()` nativo disponível). O Controller passa esse paginator direto para `self::successResponse($data, ...)` — **não** foi observado o uso de `Resource::collection($paginated)` no domínio `Task` (diferente da recomendação de outros harnesses AE3 de nunca embrulhar manualmente); aqui o paginator vai dentro do envelope padrão via `successResponse()`. Se for criar uma listagem nova baseada em `Eloquent\Builder` puro (sem CTE), prefira `Resource::collection($paginated)` quando fizer sentido, mas siga o padrão real (`successResponse($paginator, ...)`) para queries em CTE/`DB::select()` como a de tarefas.

## Erro

```php
catch (\Exception $e) {
    return self::returnError($e);
}
```

Confirmado em todos os métodos de todos os Controllers lidos (`TaskController`, `AdminUserController` — inferido pela consistência do padrão). Nunca `self::errorResponse()` — esse método não existe na trait `Response` real deste projeto (ver `utilities.md`).

## Validação falha

`FormRequest` real (`CreateTaskRequest`) **não sobrescreve `failedValidation()`** — usa o comportamento padrão do Laravel (`ValidationException`), que é capturado pelo handler global em `bootstrap/app.php` (`$exceptions->render(fn ($t, $r) => Response::returnError($t))`), que por sua vez despacha para `failedValidationResponse($exception)`. **Isso diverge de outros projetos AE3**, onde cada `FormRequest` sobrescreve `failedValidation()` explicitamente — aqui não é necessário replicar esse boilerplate por Request, o handler global já cobre. Não adicione `failedValidation()` a uma Request nova só por hábito de outro projeto; o comportamento real já funciona sem isso.
