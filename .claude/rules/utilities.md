# Utilitários

## Trait `Response` (`App\Base\Traits\Response`)

API real confirmada lendo `app/Base/Traits/Response.php` — **não confunda com a API de outros projetos AE3** (nomes de método são diferentes aqui):

| Método | Uso real |
|--------|----------|
| `successResponse(data, message, status_code, return_data_null)` | Resposta de sucesso `{success, message, data}`. Default `status_code = 200`. |
| `successResponseJson(data, message, status_code)` | Variante direta, sem passar pelo pipeline de `defineResponseData` (sempre inclui `data`, mesmo `null`). |
| `returnError($exception)` | **Ponto central de tratamento de erro — este é o nome real, não `errorResponse()`.** Despacha por tipo de exception (tabela abaixo). |
| `internalServerErrorResponse($exception, message)` | 500 |
| `modelNotFoundResponse($exception, message)` | 404 |
| `invalidArgumentResponse($exception, message)` | 400 (não 422 — diferente de outros projetos AE3, confira antes de assumir) |
| `queryExceptionResponse($exception, message)` | Extrai mensagem do PostgreSQL via regex `ERROR:/ERRO:...CONTEXT:`, tenta usar os últimos 3 dígitos do `getCode()` da exception como status HTTP se for um status HTTP válido, senão cai em 500 |
| `connectionExceptionResponse($exception)` | 400 |
| `conflictResponse($exception, message)` | 409 (`HttpResponse::HTTP_CONFLICT`) |
| `failedValidationResponse($validator)` | 422 com `data.errors`, lança `HttpResponseException` |
| `notAuthorizeExceptionResponse(message, status_code = 401)` | 401 por default, mas aceita `status_code` customizado — usado com `403` em `AuthenticateMiddleware` para permissão negada |
| `customValidationResponse(success, message, errors, status_code = 422)` | Resposta de validação customizada fora do fluxo de `$validator` |
| `httpResponseException($exception)` | Devolve a response já embutida na exception |

`returnError()` despacha automaticamente por tipo, na ordem:

| Exception | Tratamento |
|-----------|------------|
| `HttpResponseException` | devolve a response já embutida |
| `QueryException` | `queryExceptionResponse()` |
| `ModelNotFoundException` | `modelNotFoundResponse()` → 404 |
| `InvalidArgumentException` | `invalidArgumentResponse()` → 400 |
| `ConnectionException` (`Illuminate\Http\Client`) | `connectionExceptionResponse()` → 400 |
| `ConflictHttpException` | `conflictResponse()` → 409 |
| `ValidationException` | `failedValidationResponse($exception)` (nota: passa a exception, não `$exception->validator` — confirme a assinatura antes de reusar em outro contexto) |
| `Symfony\...\HttpException` (genérica) | usa `$exception->getStatusCode()` |
| Outros | `internalServerErrorResponse()` → 500 |

**Uso real no `bootstrap/app.php`**: `returnError()` é chamado também como handler global de exceptions não tratadas (`$exceptions->render(fn ($throwable, $request) => ResponseTrait::returnError($throwable))`), exceto para requests em `docs/*` (Scramble).

## Trait `CacheTrait` (`App\Base\Traits\CacheTrait`)

```php
$this->cache(string $key, callable $callback, ?int $ttl = null): mixed   // Cache::remember com fallback se Redis cair (Predis\Connection\ConnectionException)
$this->clearUserCache($user_id): void        // Cache::forget('user_id_' . $user_id)
$this->clearAccessToken($access_token): void // Cache::forget('token_' . $access_token)
$this->clearRefreshToken($refresh_token): void
$this->clearCache(string $prefix, string $key): void
```

TTL default vem de `config('api.cache.ttl')`; uso de cache é condicionado por `config('api.cache.use_cache')`. Ver uso real em `DetailUserService::execute()` (cache de detalhe por id) e `CreateTaskService::execute()` (invalidação de cache de listagem por prefixo).

## Helpers globais (confirmados via `autoload.files` do `composer.json`)

Arquivos auto-loaded: `app/Base/Helpers/{arrays,data,hashs,paths,sql,strings,user,validation}.php`. Helpers usados de fato no código lido:

```php
userObject()             // usuário autenticado atual (TaskController::index/store)
entityObject()            // objeto de autenticação em cache (AuthenticateMiddleware)
handlerRequestToken($token) // normaliza o token Bearer extraído do Request
```

Não foram lidos os arquivos de helper individualmente neste harness além do uso confirmado nos Controllers/Middleware — ao usar outro helper, confirme a assinatura real no arquivo correspondente antes de assumir comportamento.

## Autenticação (real, sem Sanctum/Passport)

```php
'auth.api'              // alias de middleware (bootstrap/app.php) → AuthenticateMiddleware
'auth.api:admin.users.list' // com verificação de ability
```

Token validado contra função **PL/pgSQL** (via `TokenInCacheService`), não contra `personal_access_tokens` do Sanctum diretamente em PHP — a tabela existe (`admin.personal_access_tokens`, ver migration) mas a leitura/validação passa por procedure de banco. Revogação: `UPDATE admin.personal_access_tokens SET expires_at = now()` + `Cache::forget`.

## Preferir a API expressiva do framework

```php
// Carbon
Carbon::parse($data->created_at)->addMinutes($expirationMinutes) < now()
```

Carbon é mutável — clone com `->copy()` antes de mutar se o valor original for reutilizado depois.

## `exists:`/`unique:` com tabela de schema qualificado (Postgres)

Mesmo cuidado de outros projetos Laravel multi-schema Postgres: o primeiro segmento antes do primeiro ponto em `exists:tabela,coluna` é interpretado como **connection**, não schema. Se for necessário validar `exists:` contra uma tabela do schema `admin`, prefixe com o nome real da connection (`pgsql`, ver `config/database.php`): `exists:pgsql.admin.roles,id`. As Requests lidas neste harness (`CreateTaskRequest`) só validam contra tabelas do schema `public` (`exists:task_statuses,id`, sem prefixo de schema porque `public` é o schema-padrão de busca do Postgres) — não foi confirmado um caso real de `exists:` contra `admin.*` no código lido; ao precisar, teste a variante com prefixo `pgsql.` primeiro.
