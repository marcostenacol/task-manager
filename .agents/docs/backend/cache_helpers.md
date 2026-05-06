# Camada de Cache Helpers

Os **Cache Helpers** são funções globais que encapsulam a lógica de recuperação de dados com persistência em memória (normalmente Redis). Eles são a espinha dorsal da performance do sistema para listagens e configurações.

## 1. Conceito e Utilização
Toda listagem de dados (combos, status, tipos, configurações) que não sofra alterações constantes deve obrigatoriamente passar por um Cache Helper. Isso evita hits desnecessários ao banco de dados e garante tempos de resposta rápidos.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Localização**: Devem ser definidos em um helper de cache (ex: `app/Packages/Cache/Helpers/data.php`).
2.  **Nomenclatura**: Seguir o padrão `entidadePluralInCache()` ou `entidadePorFiltroInCache($id)`.
3.  **Uso da CacheTrait**: Implementar via classe anônima que utiliza a `App\Base\Traits\CacheTrait`.
4.  **Chave Única**: Definir uma `key` única e descritiva para o Redis.
5.  **Callback de Dados**: Passar a query original via função anônima (`callback`).
6.  **Obrigatoriedade**: Todos os Services de listagem devem utilizar esses helpers em vez de chamar Repositories diretamente.

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Dados Transacionais**: Jamais armazene em cache listagens que mudam com alta frequência (ex: logs em tempo real).
2.  **Payloads Gigantes**: Evite colocar coleções inteiras de milhares de registros com todos os seus relacionamentos sem necessidade.
3.  **Lógica de Negócio**: O callback do cache deve conter apenas a busca de dados (`Repository` ou `Model`).

---

## 4. Invalidação de Cache ⚠️
1.  **Responsabilidade do Service**: Sempre que um `Service` realizar uma operação que invalide o dado original (ex: logout, atualização de perfil, troca de status), ele deve obrigatoriamente chamar o método `clearCache` ou `clearUserCache` da `CacheTrait`.
2.  **Chaves Relacionadas**: Certifique-se de limpar todas as chaves que dependem do dado alterado.
3.  **Segurança**: Em fluxos de autenticação, a limpeza do cache do token é crítica para evitar o uso de sessões revogadas no banco mas ainda ativas no Redis.

---

## 5. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar um novo domínio, gere automaticamente o helper de cache correspondente:
- Se a entidade for "Status de Pedido", a função será `orderStatusesInCache()`.
- Verifique se a chave de cache no Redis reflete o nome da função.

---

## 5. Exemplo de Implementação Padrão

Este é o padrão recomendado de implementação.

```php
use App\Base\Traits\CacheTrait;
use App\Packages\Domain\Module\Models\Entity;

/**
 * Recupera os registros cadastrados via Cache
 * @return mixed
 */
function entityInCache(): mixed 
{
    // 1. Uso de classe anônima com CacheTrait
    return (new class { use CacheTrait; })->cache(
        key: 'entity_list', // 2. Chave no Redis
        callback: function () {
            // 3. Busca original no banco
            return Entity::all();
        }
    );
}
```

---

## 6. Exemplo com Filtros

```php
function entityByIdInCache($id): mixed 
{
    return (new class { use CacheTrait; })->cache(
        key: 'entity_id_' . $id, // Chave dinâmica
        callback: function () use ($id) {
            return Entity::with(['relation'])->find($id);
        }
    );
}
```

---

## 7. Configurações de TTL (Time To Live)
- O tempo padrão de expiração deve ser definido via `.env` ou configuração global.
- É possível sobrescrever o TTL passando o terceiro parâmetro para o método `cache(...)`.
