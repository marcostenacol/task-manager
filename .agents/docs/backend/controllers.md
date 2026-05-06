# Camada de Controller

Os Controllers atuam estritamente como **Orquestradores de Entrada e Saída**. Eles são o ponto de contato entre o mundo externo (HTTP) e o núcleo da aplicação (Services).

## 1. Conceito e Utilização
A responsabilidade única do Controller é lidar com o protocolo HTTP: interpretar a requisição, delegar o processamento para a camada de serviço e formatar a resposta. Os controllers devem ser enxutos e delegar 100% da lógica de negócio.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Herança**: Deve sempre estender `App\Base\Http\Controllers\BaseController`.
2.  **Injeção de Dependência**: Injetar o `Service` específico no método da ação (Method Injection).
3.  **Extração de Parâmetros**: Capturar dados da `Request` ou `Validated Request` e passá-los de forma explícita para o Service.
4.  **Tratamento de Exceções**: Utilizar blocos `try-catch` em todas as operações de escrita ou complexas.
5.  **Padronização de Resposta**:
    - Sucesso: Retornar obrigatoriamente via `self::successResponse(...)`.
    - Erro: Retornar obrigatoriamente via `self::returnError($exception)`.
6.  **Tipagem**: Definir o retorno do método como `JsonResponse|Response`.

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Regra de Negócio**: Jamais implementar `if`, `foreach` ou cálculos de negócio dentro do Controller.
2.  **Queries de Banco**: É terminantemente proibido o uso de `Model::query()`, `DB::table()` ou chamadas diretas a Repositories no Controller.
3.  **Tratamento de Dados**: Não formate strings, datas ou arrays. Isso deve ser feito nos Services ou Resources.
4.  **Respostas Manuais**: Não use `response()->json([...])` manualmente. Utilize os métodos da Trait `Response` herdados do `BaseController`.

---

## 4. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar ou revisar um Controller, verifique:
- Se o método ultrapassa 10-15 linhas (sinal de excesso de responsabilidade).
- Se há vazamento de lógica de banco de dados.
- Se os nomes dos métodos seguem o padrão REST (`index`, `show`, `store`, `update`, `destroy`) ou ações específicas de domínio.

---

## 5. Exemplo de Implementação Padrão

Este exemplo demonstra a criação de um registro simples seguindo todas as normas do projeto.

```php
namespace App\Packages\Domain\Module\Controllers;

use App\Base\Http\Controllers\BaseController as Controller;
use App\Packages\Domain\Module\Requests\StoreEntityRequest;
use App\Packages\Domain\Module\Services\StoreEntityService;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EntityController extends Controller
{
    /**
     * @param StoreEntityRequest $request
     * @param StoreEntityService $service
     * @return JsonResponse
     */
    public function store(StoreEntityRequest $request, StoreEntityService $service): JsonResponse
    {
        try {
            // 1. Delegação total para o Service
            // 2. Uso dos dados já validados pela Request
            $data = $service->execute(
                attributes: $request->validated()
            );

            // 3. Resposta padronizada de sucesso
            return self::successResponse(
                data: $data,
                message: 'Registro cadastrado com sucesso!',
                status_code: Response::HTTP_CREATED
            );
        } catch (Exception $exception) {
            // 4. Tratamento centralizado de erros (log + formatação)
            return self::returnError($exception);
        }
    }

    /**
     * Exemplo de Listagem Simples (Read-Only)
     */
    public function index(ListEntityService $service): JsonResponse
    {
        return self::successResponse(
            data: $service->execute()
        );
    }
}
```

---

## 6. Métodos da BaseController disponíveis
- `self::successResponse($data, $message, $status_code)`: Resposta padrão de sucesso.
- `self::returnError($exception)`: Analisa o tipo da exceção e retorna o status code HTTP correto (404, 403, 500, etc) com a mensagem apropriada.
