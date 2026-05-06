# Camada de Repository

Os Repositories são responsáveis pela **Abstração da Persistência**. Eles atuam como a ponte entre os Models (Eloquent) e as consultas otimizadas no PostgreSQL, priorizando performance através de SQL puro e CTEs (Common Table Expressions).

## 1. Conceito e Utilização
O Repository centraliza toda e qualquer interação com o banco de dados. Se você precisa buscar, salvar, deletar ou contar registros, essa lógica deve estar aqui. Incentivamos o uso de `DB::select` e `DB::selectOne` para consultas complexas que envolvam múltiplos joins ou schemas diferentes.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Herança**: Deve sempre estender `App\Base\Repository\BaseRepository`.
2.  **Modelo Principal**: Definir o model no construtor usando `$this->setModel(Model::class)`.
3.  **Isolamento de SQL**: Toda query complexa, uso de `WHERE`, `JOIN` ou `GROUP BY` deve residir no Repository.
4.  **Uso de CTEs e Subqueries**: Preferir o uso de `WITH` (CTEs) no PostgreSQL para organizar queries grandes e complexas.
5.  **Tipagem de Retorno**: Retornar objetos stdClass (de `DB::select`), Models ou coleções Eloquent.
6.  **Bindings Seguros**: Sempre utilizar parâmetros nomeados (`:param`) ou posicionais (`?`) para evitar SQL Injection.

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Lógica de Negócio**: O Repository não deve tomar decisões de negócio (ex: `if ($user->isActive)`). Ele apenas executa a query solicitada.
2.  **Acesso a Cache**: Repositories não lidam com cache. Isso é responsabilidade dos Services ou Helpers de Cache.
3.  **Dependência de Request**: Jamais acesse `request()` ou `auth()` dentro de um Repository.
4.  **Eloquent Desordenado**: Evite encadear métodos Eloquent pesados fora do Repository (ex: fazer `User::with(...)->where(...)->get()` no Service).

---

## 4. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar ou revisar um Repository, verifique:
- Se a query SQL utiliza aliases claros para tabelas (`P` para `people`, `U` para `users`).
- Se consultas que envolvem múltiplos schemas (`social`, `admin`, `public`) estão qualificando corretamente as tabelas.
- Se métodos de verificação de existência usam `SELECT EXISTS` para máxima performance.

---

## 5. Exemplo de Implementação: Eloquent Simples

```php
namespace App\Packages\Admin\User\Repositories;

use App\Base\Repository\BaseRepository;
use App\Packages\Admin\User\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        // Define o modelo que o BaseRepository usará para métodos mágicos como all(), find(), etc.
        $this->setModel(User::class);
    }
}
```

---

## 6. Exemplo de Implementação: SQL Complexo (CTE)

Este é o padrão ouro do projeto para consultas que exigem performance.

```php
public function getEntityDetail(int $id): mixed 
{
    return DB::selectOne(
        query: "WITH tmp_entity AS (
                    SELECT id, name FROM schema.table WHERE id = :id
                ), tmp_related AS (
                    SELECT entity_id, info FROM schema.related_table
                )
                SELECT 
                    E.*, 
                    R.info 
                FROM tmp_entity E
                LEFT JOIN tmp_related R ON E.id = R.entity_id",
        bindings: ['id' => $id]
    );
}
```

---

## 7. Métodos herdados do BaseRepository
Ao estender `BaseRepository`, você ganha acesso automático a:
- `all(?string $order_by)`
- `find(int|string|null $id, array $with, string $model_name)`
- `create(array $attributes)`
- `update(string|int $id, array $attributes)`
- `delete(string|int $id)`
- `findByColumn(string $column, string $value, array $with)`
