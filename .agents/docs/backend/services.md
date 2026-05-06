# Camada de Service

Os Services representam o **Coração da Aplicação**. Eles são responsáveis por processar as regras de negócio, orquestrar chamadas a Repositories, gerenciar o Cache e integrar serviços externos.

## 1. Conceito e Utilização
Cada Service deve ter uma responsabilidade única e focada em uma ação específica do domínio. Evitamos Services "genéricos" com múltiplos métodos (ex: CRUD Service). Preferimos múltiplos Services pequenos e especializados.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Nomenclatura**: Seguir obrigatoriamente o padrão `Ação + Entidade + Service` (ex: `StorePersonService`, `ListMaritalStatusService`).
2.  **Método Principal**: O ponto de entrada público deve ser sempre o método `execute()`.
3.  **Injeção de Repositories**: Utilizar o construtor para injetar os Repositories necessários.
4.  **Uso de Cache**: Em listagens, o Service deve obrigatoriamente chamar os helpers de cache (ex: `sexInCache()`).
5.  **Lógica de Negócio**: Validar condições de negócio, cálculos e fluxos de decisão.
6.  **Retorno de Dados**: Retornar dados puros (Arrays, Objects) ou `Resources` (Transformers).

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Dependência de HTTP**: Jamais use `request()`, `auth()->user()` ou acesse cabeçalhos HTTP dentro do Service. Receba tudo por parâmetros no `execute()`.
2.  **Queries de Banco**: Evite consultas complexas via Eloquent diretamente no Service. Delegue para os Repositories.
3.  **Respostas HTTP**: Nunca retorne `JsonResponse` ou use `response()`. O Service não sabe que está sendo chamado por uma API.
4.  **Múltiplos Métodos Públicos**: Evite criar métodos públicos como `save()`, `update()`, `list()`. Use o padrão de método único `execute()`.

---

## 4. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar ou revisar um Service, verifique:
- Se o nome segue o padrão semântico `Ação + Entidade`.
- Se a listagem está utilizando as funções `*InCache()` em vez de consultas diretas ao banco.
- Se o Service está "poluído" com lógica de persistência que deveria estar no Repository.

---

## 5. Exemplo de Implementação: Escrita (Criação)

```php
namespace App\Packages\Social\Person\Services;

use App\Packages\Social\Person\Repositories\PersonRepository;

class StorePersonService
{
    public function __construct(
        private PersonRepository $repository
    ) {}

    /**
     * @param array $attributes
     * @return mixed
     */
    public function execute(array $attributes): mixed
    {
        // 1. Lógica de negócio (ex: verificar duplicidade customizada)
        // 2. Delegação para o Repository
        return $this->repository->create($attributes);
    }
}
```

---

## 6. Exemplo de Implementação: Listagem (Com Cache)

Seguindo a regra de que toda listagem deve usar cache.

```php
namespace App\Packages\Social\Sex\Services;

use App\Packages\Social\Sex\Resources\SexResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListSexService
{
    /**
     * @return AnonymousResourceCollection
     */
    public function execute(): AnonymousResourceCollection
    {
        // 1. Chamada obrigatória ao helper de cache
        // O acesso ao banco fica isolado na função sexInCache()
        $sexes = sexInCache();

        // 2. Transformação dos dados para a saída
        return SexResource::collection($sexes);
    }
}
```

---

## 7. Integração com Banco de Dados (PL/pgSQL)

Services que lidam com processos complexos de banco podem chamar funções PL/pgSQL diretamente para máxima performance.

```php
public function execute($param1, $param2) 
{
    // Chamada de função otimizada no PostgreSQL
    $response = json_decode(DB::selectOne(
        query: "SELECT * FROM schema.your_function(?, ?);",
        bindings: [$param1, $param2]
    )->data);

    return $response;
}
```
